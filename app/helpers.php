<?php

if (!function_exists('get_browser_name')) {
  function get_browser_name($user_agent)
  {
    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
    elseif (strpos($user_agent, 'Edge')) return 'Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
    elseif (strpos($user_agent, 'Safari')) return 'Safari';
    elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
    return 'Other';
  }
}

if (!function_exists('get_platform')) {
  function get_platform($user_agent)
  {
    if (preg_match('/linux/i', $user_agent)) return 'Linux';
    elseif (preg_match('/macintosh|mac os x/i', $user_agent)) return 'Mac';
    elseif (preg_match('/windows|win32/i', $user_agent)) return 'Windows';
    return 'Other';
  }
}
