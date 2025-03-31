<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DatabaseReaderService
{
    protected $models = [];

    public function __construct()
    {
        // Tự động lấy tất cả các model trong thư mục app/Models
        $this->loadModels();
    }

    /**
     * Tự động tải tất cả các model từ thư mục app/Models
     */
    protected function loadModels()
    {
        $modelPath = app_path('Models/*.php');
        $modelFiles = glob($modelPath);

        foreach ($modelFiles as $file) {
            $modelName = basename($file, '.php');
            $modelClass = "App\\Models\\{$modelName}";

            if (class_exists($modelClass)) {
                $this->models[$modelName] = $modelClass;
            }
        }
    }

    /**
     * Lấy dữ liệu từ tất cả các model
     */
    public function getAllData()
    {
        $data = [];

        foreach ($this->models as $modelName => $modelClass) {
            try {
                $modelInstance = new $modelClass();
                $data[strtolower($modelName)] = $modelInstance->all()->toArray();
            } catch (\Exception $e) {
                Log::error("Error fetching data from model {$modelName}: " . $e->getMessage());
                $data[strtolower($modelName)] = [];
            }
        }

        return $data;
    }

    /**
     * Tạo tóm tắt dữ liệu từ tất cả các model
     */
    public function getDataSummary()
    {
        $data = $this->getAllData();
        $summary = "Database Summary:\n";

        foreach ($data as $modelName => $records) {
            $summary .= "Total " . ucfirst($modelName) . ": " . count($records) . "\n";
        }

        return $summary;
    }

    /**
     * Lấy dữ liệu liên quan dựa trên câu hỏi
     */
    public function getRelevantData($question)
    {
        $data = $this->getAllData();
        $relevantData = [];

        // Kiểm tra câu hỏi để lấy dữ liệu liên quan
        $questionLower = strtolower($question);

        foreach ($this->models as $modelName => $modelClass) {
            $modelNameLower = strtolower($modelName);
            if (stripos($questionLower, $modelNameLower) !== false) {
                $relevantData[$modelNameLower] = $data[$modelNameLower];
            }
        }

        // Nếu không tìm thấy dữ liệu liên quan, trả về toàn bộ dữ liệu
        return !empty($relevantData) ? $relevantData : $data;
    }
}