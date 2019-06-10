<?php
namespace Yiisoft\Mailer;

use Yiisoft\View\ViewContextInterface;

/**
 * Template composes the message from view templates, ensuring isolated view rendering. It allows
 * changing of the rendering options such as layout during composing of the particular message without
 * affecting other messages.
 *
 * An instance of this class serves as a view context during the mail template rendering and is available
 * inside a view template file via [[\yii\base\View::context]].
 *
 * @see BaseMailer::compose()
 */
class Template implements ViewContextInterface
{
    /**
     * @var MessageInterface related mail message instance.
     */
    public $message;
    /**
     * @var \Yiisoft\View\View view instance used for rendering.
     */
    public $view;
    /**
     * @var string path to the directory containing view files.
     */
    public $viewPath;
    /**
     * @var string|array name of the view to use as a template. The value could be:
     *
     * - a string that contains either a view name for rendering HTML body of the email.
     *   The text body in this case is generated by applying `strip_tags()` to the HTML body.
     * - an array with 'html' and/or 'text' elements. The 'html' element refers to a view name
     *   for rendering the HTML body, while 'text' element is for rendering the text body. For example,
     *   `['html' => 'contact-html', 'text' => 'contact-text']`.
     */
    public $viewName;
    /**
     * @var string HTML layout view name. It is the layout used to render HTML mail body.
     * The property can take the following values:
     *
     * - a relative view name: a view file relative to [[viewPath]], e.g., 'layouts/html'.
     * - an empty string: the layout is disabled.
     */
    public $htmlLayout = '';
    /**
     * @var string text layout view name. This is the layout used to render TEXT mail body.
     * Please refer to [[htmlLayout]] for possible values that this property can take.
     */
    public $textLayout = '';


    /**
     * {@inheritdoc}
     */
    public function getViewPath(): string
    {
        return $this->viewPath;
    }

    /**
     * Composes the given mail message according to this template.
     * @param MessageInterface $message the message to be composed.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     */
    public function compose(MessageInterface $message, $params = [])
    {
        $this->message = $message;

        if (is_array($this->viewName)) {
            if (isset($this->viewName['html'])) {
                $html = $this->render($this->viewName['html'], $params, $this->htmlLayout);
            }
            if (isset($this->viewName['text'])) {
                $text = $this->render($this->viewName['text'], $params, $this->textLayout);
            }
        } else {
            $html = $this->render($this->viewName, $params, $this->htmlLayout);
        }

        if (isset($html)) {
            $this->message->setHtmlBody($html);
        }
        if (isset($text)) {
            $this->message->setTextBody($text);
        } elseif (isset($html)) {
            if (preg_match('~<body[^>]*>(.*?)</body>~is', $html, $match)) {
                $html = $match[1];
            }
            // remove style and script
            $html = preg_replace('~<((style|script))[^>]*>(.*?)</\1>~is', '', $html);
            // strip all HTML tags and decode HTML entities
            $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
            // improve whitespace
            $text = preg_replace("~^[ \t]+~m", '', trim($text));
            $text = preg_replace('~\R\R+~mu', "\n\n", $text);
            $this->message->setTextBody($text);
        }
    }

    /**
     * Renders the view specified with optional parameters and layout.
     * The view will be rendered using the [[view]] component.
     * @param string $view a view name of the view file.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @param string $layout layout view name. If the value is empty, no layout will be applied.
     * @return string the rendering result.
     */
    public function render(string $view, array $params = [], string $layout = ''): string
    {
        $output = $this->view->render($view, $params, $this);
        if ($layout === '') {
            return $output;
        }
        return $this->view->render($layout, ['content' => $output], $this);
    }
}
