<?php

namespace App\Http\Controllers;

use App\Services\DatabaseReaderService;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class AIController extends Controller
{
    protected $databaseReader;
    protected $geminiApiKey;

    public function __construct(DatabaseReaderService $databaseReader)
    {
        $this->databaseReader = $databaseReader;
        $this->geminiApiKey = env('GEMINI_API_KEY'); // Lấy API Key từ .env
    }

    public function ask(Request $request)
    {
        $question = $request->input('question');
        $sessionId = $request->input('sessionId');

        if (!$question) {
            return response()->json([
                'status' => 'error',
                'message' => 'Question is required.',
            ], 400);
        }

        // Tạo sessionId nếu không có
        if (!$sessionId) {
            $sessionId = \Str::uuid()->toString();
            Session::put('sessionId', $sessionId);
        }

        try {
            // Lấy thời gian thực
            $currentTime = Carbon::now('Asia/Ho_Chi_Minh'); // Múi giờ Việt Nam
            $currentTimeString = $currentTime->format('H:i A, l, jS F Y'); // Ví dụ: 05:53 AM, Monday, 31st March 2025
            $dayOfWeek = $currentTime->format('l'); // Lấy ngày trong tuần: Monday

            // Kiểm tra xem câu hỏi có liên quan đến database không
            $isDatabaseRelated = $this->isDatabaseRelated($question);

            // Kiểm tra xem câu hỏi có liên quan đến thời gian không
            $isTimeRelated = $this->isTimeRelated($question);

            // Lấy lịch sử trò chuyện từ session
            $history = Session::get("conversation_history_{$sessionId}", []);

            // Tạo prompt cho Gemini AI, bao gồm lịch sử trò chuyện
            $prompt = $this->buildPrompt($question, $isDatabaseRelated, $isTimeRelated, $currentTimeString, $dayOfWeek, $history);

            // Gọi API Gemini trực tiếp
            $client = new Client();
            $response = $client->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $this->geminiApiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 1024,
                    ],
                ],
            ]);

            $result = json_decode($response->getBody(), true);
            $answer = $result['candidates'][0]['content']['parts'][0]['text'];

            // Cập nhật lịch sử trò chuyện
            $history[] = ['question' => $question, 'answer' => $answer];

            // Giới hạn lịch sử để tránh vượt quá giới hạn token (giữ 5 tin nhắn gần nhất)
            if (count($history) > 5) {
                $history = array_slice($history, -5);
            }

            // Lưu lại lịch sử vào session
            Session::put("conversation_history_{$sessionId}", $history);

            return response()->json([
                'status' => 'success',
                'sessionId' => $sessionId, // Trả về sessionId để client lưu trữ
                'answer' => $answer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error communicating with Gemini AI: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa lịch sử trò chuyện
     */
    public function clearHistory(Request $request)
    {
        $sessionId = $request->input('sessionId');

        if (!$sessionId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session ID is required.',
            ], 400);
        }

        // Xóa lịch sử trò chuyện từ session
        Session::forget("conversation_history_{$sessionId}");

        return response()->json([
            'status' => 'success',
            'message' => 'Conversation history cleared.',
        ]);
    }

    /**
     * Kiểm tra xem câu hỏi có liên quan đến database không
     */
    protected function isDatabaseRelated($question)
    {
        $questionLower = strtolower($question);
        $databaseKeywords = [
            'student', 'classroom', 'grade', 'term', 'average', 'score', 'subject',
            'teacher', 'exam', 'role', 'school year', 'database', 'term average',
            'yearly average', 'subject average', 'subject yearly average'
        ];

        foreach ($databaseKeywords as $keyword) {
            if (stripos($questionLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kiểm tra xem câu hỏi có liên quan đến thời gian không
     */
    protected function isTimeRelated($question)
    {
        $questionLower = strtolower($question);
        $timeKeywords = [
            'hôm nay', 'ngày', 'thứ', 'giờ', 'thời gian', 'bây giờ',
            'today', 'date', 'day', 'time', 'current', 'hour', 'minute',
            'tháng', 'năm', 'week', 'month', 'year'
        ];

        foreach ($timeKeywords as $keyword) {
            if (stripos($questionLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tạo prompt dựa trên loại câu hỏi, bao gồm lịch sử trò chuyện
     */
    protected function buildPrompt($question, $isDatabaseRelated, $isTimeRelated, $currentTimeString, $dayOfWeek, $history)
    {
        // Luôn cung cấp thông tin thời gian thực cho Gemini AI
        $basePrompt = "You are an AI assistant with access to real-time information. The current time is: $currentTimeString.\n";

        // Thêm lịch sử trò chuyện vào prompt
        if (!empty($history)) {
            $basePrompt .= "Here is the conversation history to provide context:\n";
            foreach ($history as $index => $entry) {
                $basePrompt .= "User " . ($index + 1) . ": " . $entry['question'] . "\n";
                $basePrompt .= "AI " . ($index + 1) . ": " . $entry['answer'] . "\n";
            }
            $basePrompt .= "\nNow, answer the following question while considering the conversation history:\n";
        }

        if ($isTimeRelated) {
            // Nếu câu hỏi liên quan đến thời gian, chỉ cần cung cấp thời gian và câu hỏi
            return $basePrompt . "Answer the following question about time: $question";
        }

        if ($isDatabaseRelated) {
            // Lấy dữ liệu từ database
            $dataSummary = $this->databaseReader->getDataSummary();
            $data = $this->databaseReader->getRelevantData($question);

            return $basePrompt . "You can also answer questions based on a database. Here is the database summary:\n$dataSummary\n\nHere is the relevant database data:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n\nQuestion: $question";
        }

        // Câu hỏi không liên quan đến database hoặc thời gian, trả lời dựa trên kiến thức chung
        return $basePrompt . "Answer the following question using your general knowledge: $question";
    }
}