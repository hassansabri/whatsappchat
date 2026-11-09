<?php
class LanguageLoader
{
    private $default_lang = 'en';
    private $available_langs = ['en', 'ar'];

    public function initialize()
    {
        $CI =& get_instance();
        $segment = $CI->uri->segment(1);

        // If first URL segment is not a language, redirect with default language
        if (!in_array($segment, $this->available_langs)) {
            $uri = $CI->uri->uri_string();
            $new_url = base_url($this->default_lang . '/' . $uri);
            redirect($new_url);
        }

        // Set CI language
        $CI->config->set_item('language', $segment);
        $CI->lang->load('message', $segment);
    }
}
