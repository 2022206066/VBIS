<?php

namespace app\core;

class Request
{
    private string $basePath = "/VBIS-main/public";
    
    public function path()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string if present
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }
        
        // Check for duplicate base paths (can happen with .htaccess redirect issues)
        $duplicatePath = $this->basePath . $this->basePath;
        if (strpos($path, $duplicatePath) === 0) {
            $path = substr($path, strlen($this->basePath));
        }
        
        // Remove base path to get the relative path
        if (strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath));
        }
        
        // Remove index.php if present directly in the path
        $path = preg_replace('~^/index\.php/?~', '/', $path);
        
        // Normalize path - ensure it starts with /
        if (empty($path)) {
            $path = '/';
        } elseif ($path[0] !== '/') {
            $path = '/' . $path;
        }
        
        // Remove .php extension if present
        $path = preg_replace('~\.php$~', '', $path);
        
        return $path;
    }

    public function method()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }
} 