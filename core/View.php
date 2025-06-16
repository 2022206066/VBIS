<?php

namespace app\core;

class View
{
    public function render($view, $layout, $model = null)
    {
        $layoutContent = $this->layoutContent($layout);
        $viewContent = $this->renderView($view, $model);

        $layoutContent = str_replace('{{ RENDER_SECTION }}', $viewContent, $layoutContent);

        echo $layoutContent;
    }

    public function layoutContent($layout)
    {
        ob_start();
        include_once __DIR__ . "/../views/layouts/$layout.php";
        return ob_get_clean();
    }

    public function renderView($view, $model)
    {
        ob_start();
        include_once __DIR__ . "/../views/$view.php";
        return ob_get_clean();
    }
    
    public static function asset($path)
    {
        return Application::url('/assets' . ($path[0] === '/' ? $path : '/' . $path));
    }
    
    public static function css($path)
    {
        return self::asset('/css' . ($path[0] === '/' ? $path : '/' . $path));
    }
    
    public static function js($path)
    {
        return self::asset('/js' . ($path[0] === '/' ? $path : '/' . $path));
    }
    
    public static function img($path)
    {
        return self::asset('/img' . ($path[0] === '/' ? $path : '/' . $path));
    }
} 