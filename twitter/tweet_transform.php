<?php

    $unshortenify = 1;

    $file = fopen('my_tweets.txt', 'r');

    $curr_tz = 'America/Los_Angeles';
    date_default_timezone_set($curr_tz);

    $curr_month = '';
    $curr_day = '';
    $new_tz = $curr_tz;


    $count = 0;
    while ($line = fgets($file)) {
        // This is a hack to work around serializing something (having real newlines), storing it in mysql, then dumping it to a text file,
        // real newlines getting converted to literal newlines and then trying to unserializing it again. The serializer always thinks there's
        // one less character than there actually is.
        $line = str_replace('\n', "\n", $line);

        $pieces =  explode("\t", trim($line));

        $type = $pieces[3];
        $date = $pieces[4];
        $text = $pieces[5];

        if ($date < strtotime('2011-01-01')) {
            continue;
        }

        $bag = unserialize($pieces[8]);

        $leaving_sf_to_nc = 1315808700;
        $leaving_nc_to_sf = 1316178000;
        $leaving_sf_to_ny = 1318096800;
        $leaving_ny_to_sf = 1318615200;
        $leaving_sf_to_nc_dec = 1324569600;
        $leaving_east_coast_to_sf = 1325367000;

        // I'm saying a day is from 4am to 4am. 
        if ($date >= $leaving_sf_to_nc && $date < $leaving_nc_to_sf) {
            $new_tz = 'America/New_York'; 
        } elseif ($date >= $leaving_nc_to_sf && $date < $leaving_sf_to_ny) {
            $new_tz = 'America/Los_Angeles'; 
        } elseif ($date >= $leaving_sf_to_ny && $date < $leaving_ny_to_sf) {
            $new_tz = 'America/New_York'; 
        } elseif ($date >= $leaving_ny_to_sf && $date < $leaving_sf_to_nc_dec) {
            $new_tz = 'America/Los_Angeles'; 
        } elseif ($date >= $leaving_sf_to_nc_dec && $date < $leaving_east_coast_to_sf) {
            $new_tz = 'America/New_York'; 
        } elseif ($date >= $leaving_east_coast_to_sf) {
            $new_tz = 'America/Los_Angeles'; 
        }

        if ($new_tz != $curr_tz) {
            $curr_tz = $new_tz;
            date_default_timezone_set($curr_tz);
        }

        $month = date('F', $date);
        $day = date('jS', $date);

        // Only worry about printing date time lines
        // when the it's 4am or later.
        if (date('H', $date) >= 4) {
            if ($month != $curr_month) {
                if ($curr_month) {
                    print '\clearpage' . "\n"; 
                }
                print '\section{' . $month . "}\n";
                $curr_month = $month;
                $curr_day = '';
            }

            if ($day != $curr_day) {
                print '\subsection{the ' . $day . "}\n"; 
                $curr_day = $day;
            }
        }

        if ($type == 2 && $bag) {
            $text = "RT @{$bag['rt']['screenname']}: {$bag['rt']['text']}";
        }

        $text = clean_text($text);

        print date('g:ia', $date) . "---" . $text . "\n\n";
    }

    function clean_text($text) {
        $text = str_replace(array('&', '$', '_', '#', '\n'), array('\&', '\$', '\_', '\#', ' '), $text);
        $text = preg_replace('#\s+#', ' ', $text);
        $text = preg_replace_callback('#(https?://[^ ]+)\b#', 'clean_url', $text);

        return $text;
    }

    function clean_url($matches) {
        global $unshortenify;

        $url = $matches[1];

        if ($unshortenify) {
            if (preg_match('#^http://t.co/#', $matches[1]) ||
                    preg_match('#^http://goo.gl/#', $matches[1]) ||
                    preg_match('#^http://goo.gl/#', $matches[1]) ||
                    preg_match('#^http://flic.kr/#', $matches[1]) ||
                    preg_match('#^http://bit.ly/#', $matches[1])
                ) {
                $ch = curl_init($matches[1]);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $ret = curl_exec($ch);
                $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                curl_close($ch);
            }
        }

        return '\url{' . $url . '}';
    }

