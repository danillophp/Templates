<?php if(!defined('ABSPATH')) exit;
function andre_opt($k,$d=[]){ $v=get_option($k,$d); return is_array($v)?$v:$d; }
function andre_sanitize_text($v){ return sanitize_text_field((string)$v); }
