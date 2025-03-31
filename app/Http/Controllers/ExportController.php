<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Score;
use App\Models\Student;
use App\Models\Exam;
use App\Models\Term;
use App\Models\SchoolYear;
use App\Models\StudentTermAverage;
use App\Models\StudentYearlyAverage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    /**
     * Export điểm của học sinh đã xác thực
     */
    public function exportStudentScores(Request $request)
    {
        try {
            // Kiểm tra người dùng đã xác thực và có role là học sinh (R2)
            $user = $request->user();
            if (!$user || $user->role_code !== 'R2') {
                Log::warning('Unauthorized access attempt to exportStudentScores.', ['user' => $user]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không có quyền truy cập. Chỉ học sinh mới được phép.',
                ], 403);
            }

            Log::info('Starting exportStudentScores for student: ' . $user->student_code);

            // Lấy điểm của học sinh đã xác thực
            $scores = Score::select('scores.*')
                ->join('students', 'scores.student_code', '=', 'students.student_code')
                ->join('exams', 'scores.exam_code', '=', 'exams.exam_code')
                ->where('scores.student_code', $user->student_code)
                ->get();

            if ($scores->isEmpty()) {
                Log::warning('No scores found for student: ' . $user->student_code);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy điểm nào để export.',
                ], 404);
            }

            // Tạo file Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Student Code');
            $sheet->setCellValue('B1', 'Name');
            $sheet->setCellValue('C1', 'Exam Name');
            $sheet->setCellValue('D1', 'Score Value');
            $sheet->getStyle('A1:D1')->getFont()->setBold(true);

            $row = 2;
            foreach ($scores as $score) {
                $sheet->setCellValue('A' . $row, $score->student_code);
                $sheet->setCellValue('B' . $row, $score->student->name);
                $sheet->setCellValue('C' . $row, $score->exam->exam_name);
                $sheet->setCellValue('D' . $row, $score->score_value);
                $row++;
            }

            foreach (range('A', 'D') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Lưu file Excel vào buffer
            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            $buffer = ob_get_clean();

            // Upload file lên CDN
            $response = Http::withBody($buffer, 'application/octet-stream')
                ->post('http://localhost:4000/cdn/upload-scores?type=php');

            if ($response->successful()) {
                $fileUrl = 'http://localhost:4000' . $response->json('url');
                $fileName = basename($fileUrl); // Lấy tên file từ URL
                Log::info('File uploaded to CDN: ' . $fileUrl);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Điểm của bạn đã được export thành công',
                    'url' => $fileUrl,
                    'downloadUrl' => "http://localhost:4000/cdn/download/{$fileName}",
                ]);
            } else {
                Log::error('Error uploading to CDN: ' . $response->body());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lỗi khi upload file điểm lên CDN',
                    'error' => $response->body(),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("Error in exportStudentScores: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi export điểm của bạn',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportScores()
    {
        try {
            Log::info('Starting exportScores...');
            $scores = Score::select('scores.*')
                ->join('students', 'scores.student_code', '=', 'students.student_code')
                ->join('exams', 'scores.exam_code', '=', 'exams.exam_code')
                ->get();

            if ($scores->isEmpty()) {
                Log::warning('No scores found to export.');
                return response()->json(['status' => 'error', 'message' => 'No scores found to export.'], 404);
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Student Code');
            $sheet->setCellValue('B1', 'Name');
            $sheet->setCellValue('C1', 'Exam Name');
            $sheet->setCellValue('D1', 'Score Value');
            $sheet->getStyle('A1:D1')->getFont()->setBold(true);

            $row = 2;
            foreach ($scores as $score) {
                $sheet->setCellValue('A' . $row, $score->student_code);
                $sheet->setCellValue('B' . $row, $score->student->name);
                $sheet->setCellValue('C' . $row, $score->exam->exam_name);
                $sheet->setCellValue('D' . $row, $score->score_value);
                $row++;
            }

            foreach (range('A', 'D') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            $buffer = ob_get_clean();

            $response = Http::withBody($buffer, 'application/octet-stream')
                ->post('http://localhost:4000/cdn/upload-scores?type=php');

            if ($response->successful()) {
                $fileUrl = 'http://localhost:4000' . $response->json('url');
                $fileName = basename($fileUrl); // Lấy tên file từ URL
                Log::info('File uploaded to CDN: ' . $fileUrl);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Scores exported successfully',
                    'url' => $fileUrl,
                    'downloadUrl' => "http://localhost:4000/cdn/download/{$fileName}",
                ]);
            } else {
                Log::error('Error uploading to CDN: ' . $response->body());
                return response()->json(['status' => 'error', 'message' => 'Error uploading scores to CDN', 'error' => $response->body()], 500);
            }
        } catch (\Exception $e) {
            Log::error("Error in exportScores: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error exporting scores', 'error' => $e->getMessage()], 500);
        }
    }

    public function exportStudentTermAverages()
    {
        try {
            Log::info('Starting exportStudentTermAverages...');
            $studentTermAverages = StudentTermAverage::select('student_term_averages.*')
                ->join('students', 'student_term_averages.student_code', '=', 'students.student_code')
                ->join('terms', 'student_term_averages.term_code', '=', 'terms.term_code')
                ->get();

            if ($studentTermAverages->isEmpty()) {
                Log::warning('No student term averages found to export.');
                return response()->json(['status' => 'error', 'message' => 'No student term averages found to export.'], 404);
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Student Code');
            $sheet->setCellValue('B1', 'Name');
            $sheet->setCellValue('C1', 'Term Name');
            $sheet->setCellValue('D1', 'Term Average');
            $sheet->setCellValue('E1', 'Classroom Rank');
            $sheet->setCellValue('F1', 'Grade Rank');
            $sheet->setCellValue('G1', 'Academic Performance');
            $sheet->getStyle('A1:G1')->getFont()->setBold(true);

            $row = 2;
            foreach ($studentTermAverages as $average) {
                $sheet->setCellValue('A' . $row, $average->student_code);
                $sheet->setCellValue('B' . $row, $average->student->name);
                $sheet->setCellValue('C' . $row, $average->term->term_name);
                $sheet->setCellValue('D' . $row, $average->term_average);
                $sheet->setCellValue('E' . $row, $average->classroom_rank);
                $sheet->setCellValue('F' . $row, $average->grade_rank);
                $sheet->setCellValue('G' . $row, $average->academic_performance);
                $row++;
            }

            foreach (range('A', 'G') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            $buffer = ob_get_clean();

            $response = Http::withBody($buffer, 'application/octet-stream')
                ->post('http://localhost:4000/cdn/upload-student-term-averages?type=php');

            if ($response->successful()) {
                $fileUrl = 'http://localhost:4000' . $response->json('url');
                $fileName = basename($fileUrl); // Lấy tên file từ URL
                Log::info('File uploaded to CDN: ' . $fileUrl);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Student term averages exported successfully',
                    'url' => $fileUrl,
                    'downloadUrl' => "http://localhost:4000/cdn/download/{$fileName}",
                ]);
            } else {
                Log::error('Error uploading to CDN: ' . $response->body());
                return response()->json(['status' => 'error', 'message' => 'Error uploading student term averages to CDN', 'error' => $response->body()], 500);
            }
        } catch (\Exception $e) {
            Log::error("Error in exportStudentTermAverages: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error exporting student term averages', 'error' => $e->getMessage()], 500);
        }
    }

    public function exportStudentYearlyAverages()
    {
        try {
            Log::info('Starting exportStudentYearlyAverages...');
            $studentYearlyAverages = StudentYearlyAverage::select('student_yearly_averages.*')
                ->join('students', 'student_yearly_averages.student_code', '=', 'students.student_code')
                ->join('school_years', 'student_yearly_averages.school_year_code', '=', 'school_years.school_year_code')
                ->get();

            if ($studentYearlyAverages->isEmpty()) {
                Log::warning('No student yearly averages found to export.');
                return response()->json(['status' => 'error', 'message' => 'No student yearly averages found to export.'], 404);
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Student Code');
            $sheet->setCellValue('B1', 'Name');
            $sheet->setCellValue('C1', 'School Year Name');
            $sheet->setCellValue('D1', 'Yearly Average');
            $sheet->setCellValue('E1', 'Classroom Rank');
            $sheet->setCellValue('F1', 'Grade Rank');
            $sheet->setCellValue('G1', 'Academic Performance');
            $sheet->getStyle('A1:G1')->getFont()->setBold(true);

            $row = 2;
            foreach ($studentYearlyAverages as $average) {
                if (!$average->schoolYear) continue;
                $sheet->setCellValue('A' . $row, $average->student_code);
                $sheet->setCellValue('B' . $row, $average->student->name ?? 'N/A');
                $sheet->setCellValue('C' . $row, $average->schoolYear->school_year_name ?? 'N/A');
                $sheet->setCellValue('D' . $row, $average->yearly_average);
                $sheet->setCellValue('E' . $row, $average->classroom_rank);
                $sheet->setCellValue('F' . $row, $average->grade_rank);
                $sheet->setCellValue('G' . $row, $average->academic_performance);
                $row++;
            }

            if ($row === 2) {
                Log::warning('No valid student yearly averages found to export.');
                return response()->json(['status' => 'error', 'message' => 'No valid student yearly averages found to export.'], 404);
            }

            foreach (range('A', 'G') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            $buffer = ob_get_clean();

            $response = Http::withBody($buffer, 'application/octet-stream')
                ->post('http://localhost:4000/cdn/upload-student-yearly-averages?type=php');

            if ($response->successful()) {
                $fileUrl = 'http://localhost:4000' . $response->json('url');
                $fileName = basename($fileUrl); // Lấy tên file từ URL
                Log::info('File uploaded to CDN: ' . $fileUrl);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Student yearly averages exported successfully',
                    'url' => $fileUrl,
                    'downloadUrl' => "http://localhost:4000/cdn/download/{$fileName}",
                ]);
            } else {
                Log::error('Error uploading to CDN: ' . $response->body());
                return response()->json(['status' => 'error', 'message' => 'Error uploading student yearly averages to CDN', 'error' => $response->body()], 500);
            }
        } catch (\Exception $e) {
            Log::error("Error in exportStudentYearlyAverages: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error exporting student yearly averages', 'error' => $e->getMessage()], 500);
        }
    }
}