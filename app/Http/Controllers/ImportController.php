<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Score;
use App\Models\Exam;
use App\Models\Student;
use App\Services\AverageService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    protected $averageService;

    public function __construct(AverageService $averageService)
    {
        $this->averageService = $averageService;
    }

    /**
     * Import điểm từ file Excel vào bảng Scores.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function importScores(Request $request)
    {
        try {
            Log::info('Starting importScores...');

            // Kiểm tra file upload
            if (!$request->hasFile('file')) {
                Log::warning('No file uploaded.');
                return response()->json([
                    'status' => 'error',
                    'message' => 'No file uploaded.',
                ], 400);
            }

            $file = $request->file('file');
            if (!$file->isValid()) {
                Log::warning('Uploaded file is not valid.');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Uploaded file is not valid.',
                ], 400);
            }

            // Kiểm tra định dạng file
            $allowedExtensions = ['xlsx', 'xls'];
            $extension = $file->getClientOriginalExtension();
            if (!in_array($extension, $allowedExtensions)) {
                Log::warning('Invalid file format. Only xlsx and xls are allowed.');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid file format. Only xlsx and xls are allowed.',
                ], 400);
            }

            // Lưu file tạm thời
            $tempPath = $file->getPathname();
            Log::info('Temporary file path: ' . $tempPath);

            // Đọc file Excel
            $spreadsheet = IOFactory::load($tempPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            Log::info('Total rows in Excel file: ' . count($rows));

            if (empty($rows)) {
                Log::warning('Excel file is empty.');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Excel file is empty.',
                ], 400);
            }

            // Kiểm tra header của file Excel
            $header = array_shift($rows); // Lấy dòng đầu tiên làm header
            $expectedHeader = ['student_code', 'name', 'exam_name', 'score_value'];
            if ($header !== $expectedHeader) {
                Log::warning('Invalid Excel header. Expected: ' . json_encode($expectedHeader));
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Excel header. Expected: student_code, name, exam_name, score_value.',
                ], 400);
            }

            // Xử lý từng dòng dữ liệu
            $importedCount = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Dòng 1 là header, nên bắt đầu từ dòng 2

                // Kiểm tra dòng trống
                if (empty(array_filter($row))) {
                    Log::info("Skipping empty row at line $rowNumber");
                    continue;
                }

                // Lấy dữ liệu từ các cột
                $studentCode = $row[0]; // student_code
                $studentName = $row[1]; // name (dùng để kiểm tra hoặc thông báo)
                $examName = $row[2]; // exam_name
                $scoreValue = $row[3]; // score_value

                // Kiểm tra dữ liệu hợp lệ
                if (empty($studentCode) || empty($examName) || !isset($scoreValue)) {
                    $errors[] = "Missing required fields at row $rowNumber (student_code: $studentCode, exam_name: $examName)";
                    Log::warning("Missing required fields at row $rowNumber");
                    continue;
                }

                // Kiểm tra student_code tồn tại
                $student = Student::where('student_code', $studentCode)->first();
                if (!$student) {
                    $errors[] = "Student not found for student_code '$studentCode' at row $rowNumber";
                    Log::warning("Student not found for student_code '$studentCode' at row $rowNumber");
                    continue;
                }

                // Kiểm tra student_name khớp với student_code
                if ($student->name !== $studentName) {
                    $errors[] = "Student name '$studentName' does not match student_code '$studentCode' at row $rowNumber";
                    Log::warning("Student name '$studentName' does not match student_code '$studentCode' at row $rowNumber");
                    continue;
                }

                // Kiểm tra điểm hợp lệ
                if (!is_numeric($scoreValue) || $scoreValue < 0 || $scoreValue > 10) {
                    $errors[] = "Invalid score value at row $rowNumber: $scoreValue";
                    Log::warning("Invalid score value at row $rowNumber: $scoreValue");
                    continue;
                }

                // Tìm exam_code từ exam_name
                $exam = Exam::where('exam_name', $examName)->first();
                if (!$exam) {
                    $errors[] = "Exam not found for exam_name '$examName' at row $rowNumber";
                    Log::warning("Exam not found for exam_name '$examName' at row $rowNumber");
                    continue;
                }
                $examCode = $exam->exam_code;

                // Kiểm tra xem điểm đã tồn tại chưa
                $existingScore = Score::where('student_code', $studentCode)
                                        ->where('exam_code', $examCode)
                                        ->first();

                if ($existingScore) {
                    // Cập nhật điểm nếu đã tồn tại
                    $existingScore->score_value = (float) $scoreValue;
                    $existingScore->save();
                    Log::info("Updated score at row $rowNumber: student_code=$studentCode, exam_code=$examCode, score_value=$scoreValue");
                } else {
                    // Tạo mới điểm nếu chưa tồn tại
                    $score = new Score();
                    $score->student_code = $studentCode;
                    $score->exam_code = $examCode;
                    $score->score_value = (float) $scoreValue;
                    $score->save();
                    Log::info("Imported score at row $rowNumber: student_code=$studentCode, exam_code=$examCode, score_value=$scoreValue");
                }

                // Cập nhật điểm trung bình sau khi thêm/cập nhật điểm
                $this->averageService->updateAverages($studentCode, $examCode);

                $importedCount++;
            }

            // Trả về kết quả
            $response = [
                'status' => 'success',
                'message' => "Imported/Updated $importedCount scores successfully.",
                'imported_count' => $importedCount,
            ];

            if (!empty($errors)) {
                $response['status'] = 'partial_success';
                $response['message'] = "Imported/Updated $importedCount scores with some errors.";
                $response['errors'] = $errors;
            }

            Log::info("Import completed: $importedCount scores imported/updated.");
            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error("Error in importScores: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while importing scores.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}