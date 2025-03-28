<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeacherService
{
    /**
     * Kiểm tra và gán giáo viên nhận dạy lớp.
     *
     * @param Teacher $teacher Giáo viên đang xác thực
     * @param string $classroomCode Mã lớp học
     * @return array Danh sách môn được gán
     * @throws \Exception
     */
    public function assignTeachingClassroom(Teacher $teacher, string $classroomCode): array
    {
        // Tìm lớp học
        $classroom = Classroom::where('classroom_code', $classroomCode)->first();
        if (!$classroom) {
            throw new \Exception('Không tìm thấy lớp học');
        }

        // Lấy danh sách tất cả môn học
        $allSubjects = Subject::pluck('subject_code')->toArray();
        if (empty($allSubjects)) {
            throw new \Exception('Chưa có môn học nào trong hệ thống');
        }

        // Lấy danh sách môn mà giáo viên này có thể dạy (dựa trên teacher_subject)
        $teacherSubjectsQuery = $teacher->subjects();
        \Log::info('SQL Query for teacher subjects: ' . $teacherSubjectsQuery->toSql());
        \Log::info('Bindings: ' . json_encode($teacherSubjectsQuery->getBindings()));
        $teacherSubjects = $teacherSubjectsQuery->pluck('subject_code')->toArray();
        \Log::info('Teacher subjects for teacher ' . $teacher->teacher_code . ': ' . json_encode($teacherSubjects));
        if (empty($teacherSubjects)) {
            throw new \Exception('Giáo viên này chưa được gán môn học nào');
        }

        // Xử lý dựa trên việc lớp có giáo viên chủ nhiệm hay không
        if ($classroom->homeroom_teacher_code) {
            return $this->handleWithHomeroomTeacher($teacher, $classroom, $allSubjects, $teacherSubjects);
        } else {
            return $this->handleWithoutHomeroomTeacher($teacher, $classroom, $allSubjects, $teacherSubjects);
        }
    }

    /**
     * Lấy danh sách môn còn lại mà lớp cần.
     *
     * @param string $classroomCode
     * @param array $allSubjects
     * @return array
     */
    private function getRemainingSubjects(string $classroomCode, array $allSubjects): array
    {
        $assignedSubjects = DB::table('classroom_teacher')
            ->where('classroom_code', $classroomCode)
            ->pluck('subject_code')
            ->toArray();

        return array_diff($allSubjects, $assignedSubjects);
    }

    /**
     * Xử lý trường hợp lớp đã có giáo viên chủ nhiệm.
     *
     * @param Teacher $teacher
     * @param Classroom $classroom
     * @param array $allSubjects
     * @param array $teacherSubjects
     * @return array
     * @throws \Exception
     */
    private function handleWithHomeroomTeacher(Teacher $teacher, Classroom $classroom, array $allSubjects, array $teacherSubjects): array
    {
        $remainingSubjects = $this->getRemainingSubjects($classroom->classroom_code, $allSubjects);

        // Trường hợp lớp đã đủ giáo viên dạy tất cả các môn
        if (empty($remainingSubjects)) {
            throw new \Exception('Lớp đã đủ giáo viên dạy tất cả các môn');
        }

        // Lấy danh sách giáo viên bộ môn khác đã dạy lớp này
        $otherTeachersSubjects = DB::table('classroom_teacher')
            ->where('classroom_code', $classroom->classroom_code)
            ->where('teacher_code', '!=', $teacher->teacher_code)
            ->pluck('subject_code')
            ->toArray();

        // Nếu giáo viên này là giáo viên chủ nhiệm
        if ($classroom->homeroom_teacher_code === $teacher->teacher_code) {
            $subjectsToAssign = array_intersect($teacherSubjects, $remainingSubjects);
            if (empty($subjectsToAssign)) {
                throw new \Exception('Giáo viên không có môn nào phù hợp để nhận dạy');
            }
            return $this->assignSubjectsToTeacher($teacher, $classroom->classroom_code, $subjectsToAssign);
        }

        // Nếu đã có giáo viên bộ môn khác
        if (!empty($otherTeachersSubjects)) {
            // Tính các môn trùng và không trùng
            $overlappingSubjects = array_intersect($teacherSubjects, $otherTeachersSubjects);
            $nonOverlappingSubjects = array_diff($teacherSubjects, $otherTeachersSubjects);

            // Nếu các môn giáo viên xin dạy trùng hết với các môn đã được gán
            if (empty($nonOverlappingSubjects)) {
                throw new \Exception('Các môn giáo viên xin dạy đã được gán hết bởi các giáo viên khác');
            }

            // Lấy các môn không trùng và nằm trong danh sách môn còn lại của lớp
            $subjectsToAssign = array_intersect($nonOverlappingSubjects, $remainingSubjects);
            if (empty($subjectsToAssign)) {
                throw new \Exception('Không có môn nào phù hợp để nhận dạy');
            }

            return $this->assignSubjectsToTeacher($teacher, $classroom->classroom_code, $subjectsToAssign);
        }

        // Nếu chưa có giáo viên bộ môn khác
        $subjectsToAssign = array_intersect($teacherSubjects, $remainingSubjects);
        if (empty($subjectsToAssign)) {
            throw new \Exception('Giáo viên không có môn nào phù hợp để nhận dạy');
        }

        return $this->assignSubjectsToTeacher($teacher, $classroom->classroom_code, $subjectsToAssign);
    }

    /**
     * Xử lý trường hợp lớp chưa có giáo viên chủ nhiệm.
     *
     * @param Teacher $teacher
     * @param Classroom $classroom
     * @param array $allSubjects
     * @param array $teacherSubjects
     * @return array
     * @throws \Exception
     */
    private function handleWithoutHomeroomTeacher(Teacher $teacher, Classroom $classroom, array $allSubjects, array $teacherSubjects): array
    {
        $remainingSubjects = $this->getRemainingSubjects($classroom->classroom_code, $allSubjects);

        // Trường hợp lớp đã đủ giáo viên dạy tất cả các môn
        if (empty($remainingSubjects)) {
            throw new \Exception('Lớp đã đủ giáo viên dạy tất cả các môn');
        }

        // Lấy danh sách giáo viên bộ môn khác đã dạy lớp này
        $otherTeachersSubjects = DB::table('classroom_teacher')
            ->where('classroom_code', $classroom->classroom_code)
            ->where('teacher_code', '!=', $teacher->teacher_code)
            ->pluck('subject_code')
            ->toArray();

        // Nếu đã có giáo viên bộ môn khác
        if (!empty($otherTeachersSubjects)) {
            // Tính các môn trùng và không trùng
            $overlappingSubjects = array_intersect($teacherSubjects, $otherTeachersSubjects);
            $nonOverlappingSubjects = array_diff($teacherSubjects, $otherTeachersSubjects);

            // Nếu các môn giáo viên xin dạy trùng hết với các môn đã được gán
            if (empty($nonOverlappingSubjects)) {
                throw new \Exception('Các môn giáo viên xin dạy đã được gán hết bởi các giáo viên khác');
            }

            // Lấy các môn không trùng và nằm trong danh sách môn còn lại của lớp
            $subjectsToAssign = array_intersect($nonOverlappingSubjects, $remainingSubjects);
            if (empty($subjectsToAssign)) {
                throw new \Exception('Không có môn nào phù hợp để nhận dạy');
            }

            return $this->assignSubjectsToTeacher($teacher, $classroom->classroom_code, $subjectsToAssign);
        }

        // Nếu chưa có giáo viên bộ môn khác
        $subjectsToAssign = array_intersect($teacherSubjects, $remainingSubjects);
        if (empty($subjectsToAssign)) {
            throw new \Exception('Giáo viên không có môn nào phù hợp để nhận dạy');
        }

        return $this->assignSubjectsToTeacher($teacher, $classroom->classroom_code, $subjectsToAssign);
    }

    /**
     * Gán các môn cho giáo viên trong bảng classroom_teacher.
     *
     * @param Teacher $teacher
     * @param string $classroomCode
     * @param array $subjectsToAssign
     * @return array
     */
    private function assignSubjectsToTeacher(Teacher $teacher, string $classroomCode, array $subjectsToAssign): array
    {
        $insertData = array_map(function ($subjectCode) use ($classroomCode, $teacher) {
            return [
                'classroom_code' => $classroomCode,
                'teacher_code' => $teacher->teacher_code,
                'subject_code' => $subjectCode,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $subjectsToAssign);

        DB::table('classroom_teacher')->insert($insertData);

        return $subjectsToAssign;
    }

    /**
     * Lấy danh sách giáo viên dạy trong một lớp dựa trên classroom_code.
     *
     * @param string $classroomCode Mã lớp học
     * @return array Danh sách giáo viên và các môn họ dạy
     * @throws \Exception
     */
    public function getTeachersInClassroom(string $classroomCode): array
    {
        // Kiểm tra xem lớp có tồn tại không
        $classroom = Classroom::where('classroom_code', $classroomCode)->first();
        if (!$classroom) {
            throw new \Exception('Không tìm thấy lớp học');
        }

        // Lấy danh sách giáo viên dạy trong lớp từ bảng classroom_teacher
        $teachersInClass = DB::table('classroom_teacher')
            ->select('classroom_teacher.teacher_code', 'classroom_teacher.subject_code')
            ->where('classroom_teacher.classroom_code', $classroomCode)
            ->get()
            ->groupBy('teacher_code');

        // Nếu không có giáo viên nào dạy lớp này
        if ($teachersInClass->isEmpty()) {
            return [];
        }

        // Lấy thông tin giáo viên và môn học
        $teacherCodes = $teachersInClass->keys()->toArray();
        $teachers = Teacher::whereIn('teacher_code', $teacherCodes)->get()->keyBy('teacher_code');

        $subjectCodes = $teachersInClass->flatten()->pluck('subject_code')->unique()->toArray();
        $subjects = Subject::whereIn('subject_code', $subjectCodes)
            ->get()
            ->keyBy('subject_code')
            ->map(function ($subject) {
                return [
                    'subject_code' => $subject->subject_code,
                    'subject_name' => $subject->subject_name,
                ];
            })->toArray();

        // Xây dựng dữ liệu trả về
        $result = [];
        foreach ($teachersInClass as $teacherCode => $subjectsTaught) {
            $teacher = $teachers->get($teacherCode);
            if (!$teacher) {
                continue; // Bỏ qua nếu không tìm thấy giáo viên
            }

            $teacherData = [
                'teacher_code' => $teacher->teacher_code,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'subjects' => $subjectsTaught->map(function ($item) use ($subjects) {
                    $subject = $subjects[$item->subject_code] ?? null;
                    return $subject ? [
                        'subject_code' => $subject['subject_code'],
                        'subject_name' => $subject['subject_name'],
                    ] : null;
                })->filter()->values()->toArray(),
            ];

            $result[] = $teacherData;
        }

        return $result;
    }

    /**
     * Nhập điểm mới hoặc sửa điểm cho học sinh trong một lớp cho một bài kiểm tra cụ thể.
     *
     * @param Teacher $teacher Giáo viên đang xác thực
     * @param string $classroomCode Mã lớp học
     * @param string $examCode Mã bài kiểm tra
     * @param array $scores Danh sách điểm của học sinh
     * @return array Danh sách các điểm đã nhập hoặc sửa
     * @throws \Exception
     */
    public function enterScores(Teacher $teacher, string $classroomCode, string $examCode, array $scores): array
    {
        // Kiểm tra lớp học có tồn tại không
        $classroom = Classroom::where('classroom_code', $classroomCode)->first();
        if (!$classroom) {
            throw new \Exception('Không tìm thấy lớp học');
        }

        // Kiểm tra bài kiểm tra có tồn tại không
        $exam = Exam::where('exam_code', $examCode)->first();
        if (!$exam) {
            throw new \Exception('Không tìm thấy bài kiểm tra');
        }

        // Kiểm tra xem giáo viên có quyền nhập điểm cho môn học này trong lớp này không
        $subjectCode = $exam->subject_code;
        $isAssigned = DB::table('classroom_teacher')
            ->where('classroom_code', $classroomCode)
            ->where('teacher_code', $teacher->teacher_code)
            ->where('subject_code', $subjectCode)
            ->exists();

        if (!$isAssigned) {
            throw new \Exception('Bạn không có quyền nhập điểm cho môn học này trong lớp này');
        }

        // Lấy danh sách học sinh trong lớp
        $students = Student::where('classroom_code', $classroomCode)->pluck('student_code')->toArray();
        if (empty($students)) {
            throw new \Exception('Lớp không có học sinh nào');
        }

        // Kiểm tra danh sách điểm gửi lên
        $studentCodesInRequest = array_column($scores, 'student_code');
        $scoreData = [];
        foreach ($scores as $score) {
            $studentCode = $score['student_code'] ?? null;
            $scoreValue = $score['score_value'] ?? null;

            if (!$studentCode || !in_array($studentCode, $students)) {
                throw new \Exception("Học sinh {$studentCode} không thuộc lớp này");
            }

            if (!is_numeric($scoreValue) || $scoreValue < 0 || $scoreValue > 10) {
                throw new \Exception("Điểm của học sinh {$studentCode} không hợp lệ. Điểm phải từ 0 đến 10.");
            }

            $scoreData[$studentCode] = [
                'student_code' => $studentCode,
                'exam_code' => $examCode,
                'score_value' => $scoreValue,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Lấy tất cả điểm hiện có của bài kiểm tra này cho học sinh trong lớp
        $existingScores = Score::where('exam_code', $examCode)
            ->whereIn('student_code', $students)
            ->get()
            ->keyBy('student_code')
            ->toArray();

        // Xử lý nhập điểm mới hoặc sửa điểm trong transaction
        $result = [];
        DB::transaction(function () use ($examCode, $students, $studentCodesInRequest, $scoreData, $existingScores, &$result) {
            // Cập nhật hoặc thêm mới điểm cho các học sinh được gửi trong request
            foreach ($scoreData as $studentCode => $score) {
                $existingScore = $existingScores[$studentCode] ?? null;

                if ($existingScore) {
                    // Nếu điểm đã tồn tại, cập nhật điểm
                    Score::where('exam_code', $examCode)
                        ->where('student_code', $studentCode)
                        ->update([
                            'score_value' => $score['score_value'],
                            'updated_at' => now(),
                        ]);
                } else {
                    // Nếu điểm chưa tồn tại, thêm mới
                    Score::create([
                        'student_code' => $studentCode,
                        'exam_code' => $examCode,
                        'score_value' => $score['score_value'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Lấy lại tất cả điểm (bao gồm cả điểm không được sửa) để trả về
            $updatedScores = Score::where('exam_code', $examCode)
                ->whereIn('student_code', $students)
                ->get()
                ->map(function ($score) {
                    return [
                        'student_code' => $score->student_code,
                        'exam_code' => $score->exam_code,
                        'score_value' => $score->score_value,
                    ];
                })
                ->toArray();

            $result = $updatedScores;
        });

        return $result;
    }

    /**
     * Lấy danh sách điểm của một lớp mà giáo viên dạy.
     *
     * @param Teacher $teacher Giáo viên đang xác thực
     * @param string $classroomCode Mã lớp học
     * @param string|null $examCode Mã bài kiểm tra (tùy chọn)
     * @param string|null $subjectCode Mã môn học (tùy chọn)
     * @return array Danh sách điểm của học sinh
     * @throws \Exception
     */
    public function getClassroomScores(Teacher $teacher, string $classroomCode, ?string $examCode = null, ?string $subjectCode = null): array
    {
        // Kiểm tra lớp học có tồn tại không
        $classroom = Classroom::where('classroom_code', $classroomCode)->first();
        if (!$classroom) {
            throw new \Exception('Không tìm thấy lớp học');
        }

        // Kiểm tra xem giáo viên có dạy lớp này không
        $teacherSubjects = DB::table('classroom_teacher')
            ->where('classroom_code', $classroomCode)
            ->where('teacher_code', $teacher->teacher_code)
            ->pluck('subject_code')
            ->toArray();

        if (empty($teacherSubjects)) {
            throw new \Exception('Bạn không dạy lớp này');
        }

        // Nếu có subject_code, kiểm tra xem giáo viên có dạy môn đó trong lớp này không
        if ($subjectCode) {
            if (!in_array($subjectCode, $teacherSubjects)) {
                throw new \Exception('Bạn không dạy môn này trong lớp này');
            }
            $subjectsToQuery = [$subjectCode];
        } else {
            $subjectsToQuery = $teacherSubjects; // Lấy tất cả môn mà giáo viên dạy
        }

        // Kiểm tra exam_code nếu có
        if ($examCode) {
            $exam = Exam::where('exam_code', $examCode)->first();
            if (!$exam) {
                throw new \Exception('Không tìm thấy bài kiểm tra');
            }
            // Kiểm tra xem bài kiểm tra có thuộc môn mà giáo viên dạy không
            if (!in_array($exam->subject_code, $subjectsToQuery)) {
                throw new \Exception('Bài kiểm tra không thuộc môn bạn dạy trong lớp này');
            }
        }

        // Lấy danh sách học sinh trong lớp
        $students = Student::where('classroom_code', $classroomCode)
            ->pluck('student_code')
            ->toArray();
        if (empty($students)) {
            throw new \Exception('Lớp không có học sinh nào');
        }

        // Lấy danh sách điểm
        $query = Score::whereIn('student_code', $students)
            ->join('exams', 'scores.exam_code', '=', 'exams.exam_code')
            ->whereIn('exams.subject_code', $subjectsToQuery)
            ->select('scores.student_code', 'scores.exam_code', 'scores.score_value');

        if ($examCode) {
            $query->where('scores.exam_code', $examCode);
        }

        $scores = $query->get()->map(function ($score) {
            return [
                'student_code' => $score->student_code,
                'exam_code' => $score->exam_code,
                'score_value' => $score->score_value,
            ];
        })->toArray();

        return $scores;
    }

    /**
     * Cập nhật thông tin giáo viên.
     *
     * @param Request $request
     * @param Teacher $teacher
     * @return Teacher
     * @throws \Exception
     */
    public function updateTeacher(Request $request, Teacher $teacher): Teacher
    {
        // Log dữ liệu gửi lên để debug
        Log::info('Request headers: ' . json_encode($request->headers->all()));
        Log::info('Request data for teacher update: ' . json_encode($request->all()));
        Log::info('Request files: ' . json_encode($request->files->all()));
        Log::info('Raw request body: ' . $request->getContent());

        // Thử parse dữ liệu thô thủ công
        $rawBody = $request->getContent();
        $boundary = $request->header('content-type');
        $boundary = explode('boundary=', $boundary)[1] ?? null;
        $parsedData = [];
        $parsedFiles = [];

        if ($boundary) {
            // Chuẩn hóa boundary
            $boundary = "--" . $boundary;
            $parts = explode($boundary, $rawBody);

            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '' || $part === '--') {
                    continue;
                }

                // Tách header và nội dung
                $lines = explode("\r\n", $part);
                $disposition = null;
                $name = null;
                $filename = null;
                $contentType = null;
                $contentLines = [];
                $isHeader = true;

                foreach ($lines as $line) {
                    if ($isHeader && trim($line) === '') {
                        $isHeader = false;
                        continue;
                    }

                    if ($isHeader) {
                        if (strpos($line, 'Content-Disposition:') !== false) {
                            $disposition = $line;
                            preg_match('/name="([^"]+)"/', $disposition, $nameMatch);
                            $name = $nameMatch[1] ?? null;
                            if (strpos($disposition, 'filename=') !== false) {
                                preg_match('/filename="([^"]+)"/', $disposition, $filenameMatch);
                                $filename = $filenameMatch[1] ?? null;
                            }
                        } elseif (strpos($line, 'Content-Type:') !== false) {
                            $contentType = trim(str_replace('Content-Type:', '', $line));
                        }
                    } else {
                        if (trim($line) !== '' && !str_contains($line, $boundary)) {
                            $contentLines[] = $line;
                        }
                    }
                }

                $content = implode("\r\n", $contentLines); // Giữ nguyên định dạng cho file

                if ($name) {
                    if ($filename) {
                        // Lưu file tạm thời để xử lý
                        $tempPath = sys_get_temp_dir() . '/' . uniqid() . '_' . $filename;
                        file_put_contents($tempPath, $content);
                        $parsedFiles[$name] = new UploadedFile(
                            $tempPath,
                            $filename,
                            $contentType,
                            null,
                            true
                        );
                    } else {
                        $parsedData[$name] = trim($content);
                    }
                }
            }

            Log::info('Manually parsed data: ' . json_encode($parsedData));
            Log::info('Manually parsed files: ' . json_encode(array_keys($parsedFiles)));
        }

        // Sử dụng dữ liệu parse thủ công nếu Laravel không parse được
        $inputData = $request->all();
        if (empty($inputData) && !empty($parsedData)) {
            Log::info('Using manually parsed data because Laravel failed to parse multipart/form-data');
            $inputData = $parsedData;
        }

        // Kiểm tra xem có dữ liệu gửi lên không
        if (empty($inputData) && empty($parsedFiles)) {
            throw new \Exception('Không có dữ liệu nào được gửi lên để cập nhật. Vui lòng cung cấp ít nhất một trường: name, email, hoặc avatar.');
        }

        // Validate dữ liệu
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:teachers,email,' . $teacher->teacher_code . ',teacher_code',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048', // Ảnh tối đa 2MB
        ]);

        // Nếu validate không nhận được dữ liệu, sử dụng dữ liệu parse thủ công
        if (empty($validated) && !empty($parsedData)) {
            $validated = validator($parsedData, [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:teachers,email,' . $teacher->teacher_code . ',teacher_code',
            ])->validate();
        }

        // Kiểm tra xem có dữ liệu nào cần cập nhật không
        $hasChanges = false;

        // Cập nhật thông tin
        if (isset($validated['name'])) {
            Log::info("Updating name from {$teacher->name} to {$validated['name']}");
            $teacher->name = $validated['name'];
            $hasChanges = true;
        }
        if (isset($validated['email'])) {
            Log::info("Updating email from {$teacher->email} to {$validated['email']}");
            $teacher->email = $validated['email'];
            $hasChanges = true;
        }

        // Xử lý upload avatar nếu có
        $avatar = $request->file('avatar') ?? ($parsedFiles['avatar'] ?? null);
        if ($avatar) {
            $avatarContent = file_get_contents($avatar->getRealPath());

            // Gửi ảnh lên scoremanagementCDN
            $response = Http::withBody($avatarContent, $avatar->getMimeType())
                ->post('http://localhost:4000/cdn/upload-images?type=php');

            if ($response->successful()) {
                $avatarUrl = 'http://localhost:4000' . $response->json('url');
                Log::info("Updating avatarUrl from {$teacher->avatarUrl} to {$avatarUrl}");
                $teacher->avatarUrl = $avatarUrl;
                $hasChanges = true;
                Log::info('Avatar uploaded to CDN for teacher: ' . $avatarUrl);
            } else {
                Log::error('Error uploading avatar to CDN: ' . $response->body());
                throw new \Exception('Error uploading avatar to CDN: ' . $response->body());
            }

            // Xóa file tạm sau khi xử lý
            if (isset($parsedFiles['avatar'])) {
                @unlink($avatar->getRealPath());
            }
        }

        // Lưu thay đổi nếu có dữ liệu gửi lên
        if ($hasChanges) {
            Log::info('Data to be saved: ' . json_encode($teacher->getDirty()));
            $saved = $teacher->save();
            if (!$saved) {
                Log::error('Failed to save teacher data to database');
                throw new \Exception('Không thể lưu dữ liệu vào database');
            }
            Log::info('Teacher data updated successfully: ' . $teacher->teacher_code);
        } else {
            Log::info('No changes detected for teacher: ' . $teacher->teacher_code);
        }

        // Làm mới dữ liệu từ database để đảm bảo trả về thông tin mới nhất
        $teacher->refresh();

        return $teacher;
    }
}