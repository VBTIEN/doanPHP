<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Exam;
use App\Models\Term;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\SubjectAverage;
use App\Models\SubjectYearlyAverage;
use App\Models\StudentYearlyAverage;
use App\Models\StudentTermAverage;
use Illuminate\Support\Facades\DB;

class AverageService
{
    /**
     * Tính học lực dựa trên điểm trung bình.
     *
     * @param float $average Điểm trung bình
     * @return string Học lực
     */
    private function calculateAcademicPerformance($average)
    {
        if ($average >= 8.0) {
            return 'Giỏi';
        } elseif ($average >= 6.5) {
            return 'Khá';
        } elseif ($average >= 5.0) {
            return 'Trung bình';
        } else {
            return 'Yếu';
        }
    }

    /**
     * Cập nhật điểm trung bình khi có thay đổi trong Score.
     *
     * @param string $studentCode Mã học sinh
     * @param string $examCode Mã bài kiểm tra
     * @return void
     */
    public function updateAverages($studentCode, $examCode)
    {
        \Log::info("Updating averages for student {$studentCode} and exam {$examCode}");

        // Lấy thông tin bài kiểm tra
        $exam = Exam::where('exam_code', $examCode)->first();
        if (!$exam) {
            \Log::warning("Exam {$examCode} not found.");
            return;
        }

        $subjectCode = $exam->subject_code;
        $termCode = $exam->term_code;

        // Lấy term để lấy school_year_code
        $term = Term::where('term_code', $termCode)->first();
        if (!$term) {
            \Log::warning("Term {$termCode} not found.");
            return;
        }
        $schoolYearCode = $term->school_year_code;

        \Log::info("Processing for student: {$studentCode}, subject: {$subjectCode}, term: {$termCode}, school_year: {$schoolYearCode}");

        // Cập nhật điểm trung bình trong kỳ cho từng môn
        $this->updateSubjectTermAverage($studentCode, $subjectCode, $termCode);

        // Cập nhật điểm trung bình học kỳ của học sinh (tất cả các môn)
        $this->updateStudentTermAverage($studentCode, $termCode);

        // Cập nhật điểm trung bình cả năm của môn học
        $this->updateSubjectYearlyAverage($studentCode, $subjectCode, $schoolYearCode);

        // Cập nhật điểm trung bình cả năm và thứ hạng của học sinh
        $this->updateStudentYearlyAverage($studentCode, $schoolYearCode);
    }

    /**
     * Cập nhật điểm trung bình trong kỳ cho một môn học.
     *
     * @param string $studentCode
     * @param string $subjectCode
     * @param string $termCode
     * @return void
     */
    private function updateSubjectTermAverage($studentCode, $subjectCode, $termCode)
    {
        \Log::info("Calculating term average for student {$studentCode}, subject {$subjectCode}, term {$termCode}");

        // Tính điểm trung bình trong kỳ
        $termAverage = Score::where('student_code', $studentCode)
            ->join('exams', 'scores.exam_code', '=', 'exams.exam_code')
            ->where('exams.subject_code', $subjectCode)
            ->where('exams.term_code', $termCode)
            ->avg('scores.score_value');

        \Log::info("Term average for student {$studentCode}, subject {$subjectCode}, term {$termCode}: " . ($termAverage ?? 'null'));

        // Cập nhật hoặc tạo mới bản ghi SubjectAverage
        SubjectAverage::updateOrCreate(
            [
                'student_code' => $studentCode,
                'subject_code' => $subjectCode,
                'term_code' => $termCode,
            ],
            [
                'term_average' => $termAverage ?? 0,
            ]
        );
    }

    /**
     * Cập nhật điểm trung bình học kỳ của học sinh (tất cả các môn).
     *
     * @param string $studentCode
     * @param string $termCode
     * @return void
     */
    private function updateStudentTermAverage($studentCode, $termCode)
    {
        \Log::info("Calculating term average for student {$studentCode}, term {$termCode}");

        // Tính điểm trung bình học kỳ của học sinh (trung bình của tất cả các môn trong học kỳ)
        $termAverage = SubjectAverage::where('student_code', $studentCode)
            ->where('term_code', $termCode)
            ->avg('term_average');

        \Log::info("Term average for student {$studentCode}, term {$termCode}: " . ($termAverage ?? 'null'));

        // Tính học lực dựa trên điểm trung bình học kỳ
        $academicPerformance = $this->calculateAcademicPerformance($termAverage ?? 0);

        // Cập nhật hoặc tạo mới bản ghi StudentTermAverage
        $studentTermAverage = StudentTermAverage::updateOrCreate(
            [
                'student_code' => $studentCode,
                'term_code' => $termCode,
            ],
            [
                'term_average' => $termAverage ?? 0,
                'academic_performance' => $academicPerformance, // Thêm học lực
            ]
        );

        // Cập nhật thứ hạng học kỳ
        $this->updateTermRankings($studentCode, $termCode);
    }

    /**
     * Cập nhật thứ hạng trong lớp và trong khối cho điểm trung bình học kỳ.
     *
     * @param string $studentCode
     * @param string $termCode
     * @return void
     */
    private function updateTermRankings($studentCode, $termCode)
    {
        \Log::info("Updating term rankings for student {$studentCode}, term {$termCode}");

        // Lấy thông tin học sinh và mối quan hệ với classroom
        $student = Student::with('classroom')->where('student_code', $studentCode)->first();
        if (!$student) {
            \Log::warning("Student {$studentCode} not found.");
            return;
        }

        if (!$student->classroom) {
            \Log::warning("Classroom for student {$studentCode} not found.");
            return;
        }

        $classroomCode = $student->classroom_code;
        $gradeCode = $student->classroom->grade_code;

        // Lấy tất cả học sinh trong lớp
        $classroomStudents = Student::where('classroom_code', $classroomCode)
            ->pluck('student_code')
            ->toArray();

        // Lấy tất cả học sinh trong khối (dựa trên grade_code từ classroom)
        $gradeStudents = Student::whereHas('classroom', function ($query) use ($gradeCode) {
            $query->where('grade_code', $gradeCode);
        })
            ->pluck('student_code')
            ->toArray();

        \Log::info("Classroom students for classroom {$classroomCode}: " . json_encode($classroomStudents));
        \Log::info("Grade students for grade {$gradeCode}: " . json_encode($gradeStudents));

        // Lấy điểm trung bình học kỳ của tất cả học sinh trong lớp
        $classroomAverages = StudentTermAverage::where('term_code', $termCode)
            ->whereIn('student_code', $classroomStudents)
            ->orderBy('term_average', 'desc')
            ->orderBy('student_code', 'asc') // Tiêu chí phụ: nếu điểm bằng nhau, sắp xếp theo student_code
            ->get(['student_code', 'term_average'])
            ->values() // Đảm bảo index liên tục từ 0
            ->mapWithKeys(function ($item, $index) {
                return [$item->student_code => $index + 1];
            })
            ->toArray();

        // Lấy điểm trung bình học kỳ của tất cả học sinh trong khối
        $gradeAverages = StudentTermAverage::where('term_code', $termCode)
            ->whereIn('student_code', $gradeStudents)
            ->orderBy('term_average', 'desc')
            ->orderBy('student_code', 'asc') // Tiêu chí phụ: nếu điểm bằng nhau, sắp xếp theo student_code
            ->get(['student_code', 'term_average'])
            ->values() // Đảm bảo index liên tục từ 0
            ->mapWithKeys(function ($item, $index) {
                return [$item->student_code => $index + 1];
            })
            ->toArray();

        \Log::info("Classroom term rankings: " . json_encode($classroomAverages));
        \Log::info("Grade term rankings: " . json_encode($gradeAverages));

        // Cập nhật thứ hạng cho tất cả học sinh trong lớp
        foreach ($classroomAverages as $studentCode => $rank) {
            StudentTermAverage::where('student_code', $studentCode)
                ->where('term_code', $termCode)
                ->update(['classroom_rank' => $rank]);
        }

        // Cập nhật thứ hạng cho tất cả học sinh trong khối
        foreach ($gradeAverages as $studentCode => $rank) {
            StudentTermAverage::where('student_code', $studentCode)
                ->where('term_code', $termCode)
                ->update(['grade_rank' => $rank]);
        }

        // Ghi log thông tin xếp hạng của học sinh hiện tại
        $studentTermAverage = StudentTermAverage::where('student_code', $studentCode)
            ->where('term_code', $termCode)
            ->first();

        if ($studentTermAverage) {
            \Log::info("Updated term rankings for student {$studentCode}: classroom_rank = {$studentTermAverage->classroom_rank}, grade_rank = {$studentTermAverage->grade_rank}");
        } else {
            \Log::warning("StudentTermAverage for student {$studentCode} and term {$termCode} not found.");
        }
    }

    /**
     * Cập nhật điểm trung bình cả năm cho một môn học.
     *
     * @param string $studentCode
     * @param string $subjectCode
     * @param string $schoolYearCode
     * @return void
     */
    private function updateSubjectYearlyAverage($studentCode, $subjectCode, $schoolYearCode)
    {
        \Log::info("Calculating yearly average for student {$studentCode}, subject {$subjectCode}, school_year {$schoolYearCode}");

        // Lấy tất cả term_codes thuộc school_year_code
        $termCodes = Term::where('school_year_code', $schoolYearCode)
            ->pluck('term_code')
            ->toArray();

        // Tính điểm trung bình cả năm của môn học
        $yearlyAverage = SubjectAverage::where('student_code', $studentCode)
            ->where('subject_code', $subjectCode)
            ->whereIn('term_code', $termCodes)
            ->avg('term_average');

        \Log::info("Yearly average for student {$studentCode}, subject {$subjectCode}, school_year {$schoolYearCode}: " . ($yearlyAverage ?? 'null'));

        // Cập nhật hoặc tạo mới bản ghi SubjectYearlyAverage
        SubjectYearlyAverage::updateOrCreate(
            [
                'student_code' => $studentCode,
                'subject_code' => $subjectCode,
                'school_year_code' => $schoolYearCode,
            ],
            [
                'yearly_average' => $yearlyAverage ?? 0,
            ]
        );
    }

    /**
     * Cập nhật điểm trung bình cả năm và thứ hạng của học sinh.
     *
     * @param string $studentCode
     * @param string $schoolYearCode
     * @return void
     */
    private function updateStudentYearlyAverage($studentCode, $schoolYearCode)
    {
        \Log::info("Calculating yearly average for student {$studentCode}, school_year {$schoolYearCode}");

        // Tính điểm trung bình cả năm của học sinh
        $yearlyAverage = SubjectYearlyAverage::where('student_code', $studentCode)
            ->where('school_year_code', $schoolYearCode)
            ->avg('yearly_average');

        \Log::info("Yearly average for student {$studentCode}, school_year {$schoolYearCode}: " . ($yearlyAverage ?? 'null'));

        // Tính học lực dựa trên điểm trung bình cả năm
        $academicPerformance = $this->calculateAcademicPerformance($yearlyAverage ?? 0);

        // Cập nhật hoặc tạo mới bản ghi StudentYearlyAverage
        $studentYearlyAverage = StudentYearlyAverage::updateOrCreate(
            [
                'student_code' => $studentCode,
                'school_year_code' => $schoolYearCode,
            ],
            [
                'yearly_average' => $yearlyAverage ?? 0,
                'academic_performance' => $academicPerformance, // Thêm học lực
            ]
        );

        // Cập nhật thứ hạng
        $this->updateRankings($studentCode, $schoolYearCode);
    }

    /**
     * Cập nhật thứ hạng trong lớp và trong khối cho điểm trung bình cả năm.
     *
     * @param string $studentCode
     * @param string $schoolYearCode
     * @return void
     */
    private function updateRankings($studentCode, $schoolYearCode)
    {
        \Log::info("Updating yearly rankings for student {$studentCode}, school_year {$schoolYearCode}");

        // Lấy thông tin học sinh và mối quan hệ với classroom
        $student = Student::with('classroom')->where('student_code', $studentCode)->first();
        if (!$student) {
            \Log::warning("Student {$studentCode} not found.");
            return;
        }

        if (!$student->classroom) {
            \Log::warning("Classroom for student {$studentCode} not found.");
            return;
        }

        $classroomCode = $student->classroom_code;
        $gradeCode = $student->classroom->grade_code;

        // Lấy tất cả học sinh trong lớp
        $classroomStudents = Student::where('classroom_code', $classroomCode)
            ->pluck('student_code')
            ->toArray();

        // Lấy tất cả học sinh trong khối (dựa trên grade_code từ classroom)
        $gradeStudents = Student::whereHas('classroom', function ($query) use ($gradeCode) {
            $query->where('grade_code', $gradeCode);
        })
            ->pluck('student_code')
            ->toArray();

        \Log::info("Classroom students for classroom {$classroomCode}: " . json_encode($classroomStudents));
        \Log::info("Grade students for grade {$gradeCode}: " . json_encode($gradeStudents));

        // Lấy điểm trung bình cả năm của tất cả học sinh trong lớp
        $classroomAverages = StudentYearlyAverage::where('school_year_code', $schoolYearCode)
            ->whereIn('student_code', $classroomStudents)
            ->orderBy('yearly_average', 'desc')
            ->orderBy('student_code', 'asc') // Tiêu chí phụ: nếu điểm bằng nhau, sắp xếp theo student_code
            ->get(['student_code', 'yearly_average'])
            ->values() // Đảm bảo index liên tục từ 0
            ->mapWithKeys(function ($item, $index) {
                return [$item->student_code => $index + 1];
            })
            ->toArray();

        // Lấy điểm trung bình cả năm của tất cả học sinh trong khối
        $gradeAverages = StudentYearlyAverage::where('school_year_code', $schoolYearCode)
            ->whereIn('student_code', $gradeStudents)
            ->orderBy('yearly_average', 'desc')
            ->orderBy('student_code', 'asc') // Tiêu chí phụ: nếu điểm bằng nhau, sắp xếp theo student_code
            ->get(['student_code', 'yearly_average'])
            ->values() // Đảm bảo index liên tục từ 0
            ->mapWithKeys(function ($item, $index) {
                return [$item->student_code => $index + 1];
            })
            ->toArray();

        \Log::info("Classroom yearly rankings: " . json_encode($classroomAverages));
        \Log::info("Grade yearly rankings: " . json_encode($gradeAverages));

        // Cập nhật thứ hạng cho tất cả học sinh trong lớp
        foreach ($classroomAverages as $studentCode => $rank) {
            StudentYearlyAverage::where('student_code', $studentCode)
                ->where('school_year_code', $schoolYearCode)
                ->update(['classroom_rank' => $rank]);
        }

        // Cập nhật thứ hạng cho tất cả học sinh trong khối
        foreach ($gradeAverages as $studentCode => $rank) {
            StudentYearlyAverage::where('student_code', $studentCode)
                ->where('school_year_code', $schoolYearCode)
                ->update(['grade_rank' => $rank]);
        }

        // Ghi log thông tin xếp hạng của học sinh hiện tại
        $studentYearlyAverage = StudentYearlyAverage::where('student_code', $studentCode)
            ->where('school_year_code', $schoolYearCode)
            ->first();

        if ($studentYearlyAverage) {
            \Log::info("Updated yearly rankings for student {$studentCode}: classroom_rank = {$studentYearlyAverage->classroom_rank}, grade_rank = {$studentYearlyAverage->grade_rank}");
        } else {
            \Log::warning("StudentYearlyAverage for student {$studentCode} and school_year {$schoolYearCode} not found.");
        }
    }
}