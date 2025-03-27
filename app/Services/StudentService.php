<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StudentService
{
    /**
     * Lấy danh sách điểm của học sinh với bộ lọc tùy chọn.
     *
     * @param string $studentCode Mã học sinh
     * @param string|null $subjectCode Mã môn học (tùy chọn)
     * @param string|null $termCode Mã kỳ học (tùy chọn)
     * @return array Danh sách điểm
     */
    public function getStudentScores(string $studentCode, ?string $subjectCode = null, ?string $termCode = null): array
    {
        // Truy vấn bảng scores và join với exams để lấy term_code và subject_code
        $query = Score::select(
            'scores.exam_code',
            'exams.term_code',
            'exams.subject_code',
            'scores.score_value'
        )
            ->join('exams', 'scores.exam_code', '=', 'exams.exam_code')
            ->where('scores.student_code', $studentCode);

        // Áp dụng bộ lọc subject_code nếu có
        if ($subjectCode) {
            $query->where('exams.subject_code', $subjectCode);
        }

        // Áp dụng bộ lọc term_code nếu có
        if ($termCode) {
            $query->where('exams.term_code', $termCode);
        }

        // Lấy kết quả
        $scores = $query->get()->map(function ($score) {
            return [
                'exam_code' => $score->exam_code,
                'term_code' => $score->term_code,
                'subject_code' => $score->subject_code,
                'score_value' => $score->score_value,
            ];
        })->toArray();

        return $scores;
    }

    /**
     * Cập nhật thông tin học sinh.
     *
     * @param Request $request
     * @param Student $student
     * @return Student
     * @throws \Exception
     */
    public function updateStudent(Request $request, Student $student): Student
    {
        // Log dữ liệu gửi lên để debug
        Log::info('Request headers: ' . json_encode($request->headers->all()));
        Log::info('Request data for student update: ' . json_encode($request->all()));
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
            'email' => 'sometimes|email|unique:students,email,' . $student->student_code . ',student_code',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048', // Ảnh tối đa 2MB
        ]);

        // Nếu validate không nhận được dữ liệu, sử dụng dữ liệu parse thủ công
        if (empty($validated) && !empty($parsedData)) {
            $validated = validator($parsedData, [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:students,email,' . $student->student_code . ',student_code',
            ])->validate();
        }

        // Kiểm tra xem có dữ liệu nào cần cập nhật không
        $hasChanges = false;

        // Cập nhật thông tin
        if (isset($validated['name'])) {
            Log::info("Updating name from {$student->name} to {$validated['name']}");
            $student->name = $validated['name'];
            $hasChanges = true;
        }
        if (isset($validated['email'])) {
            Log::info("Updating email from {$student->email} to {$validated['email']}");
            $student->email = $validated['email'];
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
                Log::info("Updating avatarUrl from {$student->avatarUrl} to {$avatarUrl}");
                $student->avatarUrl = $avatarUrl;
                $hasChanges = true;
                Log::info('Avatar uploaded to CDN for student: ' . $avatarUrl);
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
            Log::info('Data to be saved: ' . json_encode($student->getDirty()));
            $saved = $student->save();
            if (!$saved) {
                Log::error('Failed to save student data to database');
                throw new \Exception('Không thể lưu dữ liệu vào database');
            }
            Log::info('Student data updated successfully: ' . $student->student_code);
        } else {
            Log::info('No changes detected for student: ' . $student->student_code);
        }

        // Làm mới dữ liệu từ database để đảm bảo trả về thông tin mới nhất
        $student->refresh();

        return $student;
    }
}