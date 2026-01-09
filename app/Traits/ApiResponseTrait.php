<?php

namespace App\Traits;

trait ApiResponseTrait
{
    protected function respondSuccess($data = null, string $message = 'OK', int $code = 200)
    {
        $payload = ['status' => 'success', 'message' => $this->sanitizeForJson($message), 'data' => $this->sanitizeForJson($data)];
        return service('response')
            ->setStatusCode($code)
            ->setJSON($payload);
    }

    protected function respondError(string $message = 'Error', int $code = 400, $data = null)
    {
        $payload = ['status' => 'error', 'message' => $this->sanitizeForJson($message), 'data' => $this->sanitizeForJson($data)];
        return service('response')
            ->setStatusCode($code)
            ->setJSON($payload);
    }

    /**
     * Recursively sanitize data to ensure valid UTF-8 for JSON encoding.
     * Strips invalid byte sequences and removes control characters that break JSON.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function sanitizeForJson($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$this->sanitizeForJson($k)] = $this->sanitizeForJson($v);
            }
            return $out;
        }

        if (is_object($value)) {
            // Convert object to array and sanitize
            $arr = (array) $value;
            return $this->sanitizeForJson($arr);
        }

        if (is_string($value)) {
            // First, drop invalid UTF-8 sequences
            $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if ($clean === false) {
                $clean = utf8_encode($value);
            }
            // Remove C0 control characters except common whitespace
            $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $clean);
            return $clean;
        }

        // scalars (int, float, bool, null)
        return $value;
    }
}
