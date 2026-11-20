<?php

if (!defined('DOKU_INC')) die();

class syntax_plugin_dxf extends DokuWiki_Syntax_Plugin
{
    public function getType()
    {
        return 'protected';
    }

    public function getPType()
    {
        return 'block';
    }

    public function getSort()
    {
        return 190;
    }

    public function connectTo($mode)
    {
        $openPattern = '```dxf\b[^\n]*(?=\n)';
        $closePattern = '\n```[ \t]*(?=\n)';
        $pattern = '\n' .$openPattern . '(?:(?!' . $closePattern . ').)*+' . $closePattern;

        $this->Lexer->addSpecialPattern($pattern, $mode, 'plugin_dxf');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        if ($state !== DOKU_LEXER_SPECIAL) {
            return false;
        }

        $lines = preg_split('/\r?\n/', $match);
        array_shift($lines);
        array_shift($lines);
        array_pop($lines);

        return array(implode("\n", $lines));
    }

    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode !== 'xhtml') return false;
        if (!is_array($data) || !isset($data[0]) || !is_string($data[0])) return false;

        $dxfContent = $data[0];

        if (!$this->getConf('is_dxf_rendering_activated')) {
            $renderer->doc .= '<pre><code class="dxf">' . hsc($dxfContent) . '</code></pre>';
            return true;
        }

        static $viewerId = 0;
        $viewerId++;
        $id = 'dxf-viewer-' . $viewerId;

        $this->addStyles($renderer);
        $this->addScripts($renderer);

        $json = json_encode(
            $dxfContent,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
        );

        $renderer->doc .= "\n<div id=\"$id\" class=\"dxf-container\"";
        $renderer->doc .= ' data-error="' . hsc($this->getLang('error')) . '">' . "\n";
        $renderer->doc .= '  <div class="dxf-loading">' . hsc($this->getLang('loading')) . "</div>\n";
        $renderer->doc .= '  <script type="application/json" class="dxf-source">' . $json . "</script>\n";
        $renderer->doc .= "</div>\n";

        return true;
    }

    protected function addStyles(Doku_Renderer $renderer)
    {
        static $stylesAdded = false;
        if ($stylesAdded) return;

        $renderer->doc .= "\n<style>\n";
        $renderer->doc .= ".dxf-container { width: 100%; min-height: 400px; position: relative; border: 1px solid #ccc; background: #f5f5f5; overflow: hidden; }\n";
        $renderer->doc .= ".dxf-container canvas { display: block; }\n";
        $renderer->doc .= ".dxf-loading { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #666; font-size: 16px; }\n";
        $renderer->doc .= ".dxf-error { color: #d32f2f; padding: 20px; text-align: center; }\n";
        $renderer->doc .= "</style>\n";

        $stylesAdded = true;
    }

    protected function addScripts(Doku_Renderer $renderer)
    {
        static $scriptsAdded = false;
        if ($scriptsAdded) return;

        $renderer->doc .= '<script type="module" src="' . DOKU_BASE . 'lib/plugins/dxf/viewer.js"></script>' . "\n";

        $scriptsAdded = true;
    }
}
