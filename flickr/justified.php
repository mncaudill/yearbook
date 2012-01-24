<?php

    /*
        Blatantly ripped off by [redacted] and Google's Image Search

        I *could* try to be fancy and tell TeX how to layout each image but I'm going to create one big one per page (using gd) and just scale that.

        Setup:
        Define how many rows of what height and what width. 
        Define a padding that appears in all trapped spaces (ie, not on the right edge and not at the bottom edge).

        Algorithm:
        While you have less than the number of rows you need:
            Take next image from queue.
            Scale it to current row height.
            Adjust for margin.
            Place it at end of row.
            If row width is now over row width maxium, scale each image to make them fit.
    */

    $image_filename = "2011_photo_ids.txt";
    $output_dir = "pages";

    # All in pixels
    $max_height = 150;
    $max_width = 500;
    $max_page_height = round($max_width * (622 / 459)); # This comes from the \layout that I'm using in TeX
    $margin = 5;

    $image_file = fopen($image_filename, 'r');

    $images = array();
    while ($line = fgets($image_file)) {
        $line = trim($line);
        if (!$line || !file_exists($line)) continue;
        $images[] = $line;
    }

    # Faster to pop from the end than to shift from the front.
    sort($images);
    $images = array_reverse($images);

    $curr_row = array(); # list of images in current row
    $curr_row_width = 0;
    $curr_row_height = $max_height;

    $all_rows = array();

    while ($images) {
        $image = array_pop($images);

        list($width, $height) = getimagesize($image);
        
        # Scale to current row height.
        $scaling_factor = $curr_row_height / $height;
        $new_width = $width * $scaling_factor;
        $new_height = $height * $scaling_factor;

        $curr_row[] = array(
            'file' => $image,
            'width' => $new_width,
            'height' => $new_height,
            'orig_width' => $width,
            'orig_height' => $height,
        );

        $curr_row_width = $curr_row_width + $new_width;

        # Recalculate vertical margins.
        $internal_margin_width = (count($curr_row) - 1) * $margin;

        if ($curr_row_width + $internal_margin_width >= $max_width) {

            if ($curr_row_width + $internal_margin_width > $max_width) {

                # It's currently too big to fit, so let's scale all the images down and then start a new row.
                $scaling_factor = ($max_width - $internal_margin_width) / $curr_row_width;

                foreach ($curr_row as &$image) {
                    $image['width'] = $scaling_factor * $image['width']; 
                    $image['height'] = $scaling_factor * $image['height']; 
                }
                unset($image);
            }

            # This row is done!
            $all_rows[] = $curr_row;

            # Reset various things
            $curr_row = array(); 
            $curr_row_width = 0;
            $curr_row_height = $max_height;
        }
    }

    if ($curr_row) {
        $all_rows[] = $curr_row;
    }

    # Let's round!
    foreach ($all_rows as &$row) {
        foreach ($row as &$image) {
            $image['width'] = round($image['width']);
            $image['height'] = round($image['height']);
        }
        unset($image);
    }
    unset($row);

    $all_rows = array_reverse($all_rows);

    # At this point in our programming, we have a bunch of rows, with each image in a row having the same height as its rowmates.
    # Now, I want to go through and create the various images.
    # I'm going to loop through each row, add each internal margin, and when I go over max_height, figure out the height
    # and then work some imagemagick on creating a bunch of new images.

    $pages = array();
    $curr_page = array();
    $curr_page_height = 0;

    while ($all_rows) {
        $row = array_pop($all_rows);

        if (!$curr_page) {
            $curr_page[] = $row;
            $curr_page_height = $row[0]['height'];
            continue;
        }

        $curr_page_height += $margin + $row[0]['height'];

        if ($curr_page_height > $max_page_height) {
            # Put it back! No room!
            array_push($all_rows, $row);

            # Finish page and reset.
            $pages[] = $curr_page;
            $curr_page = array();
            $curr_page_height = 0;
        } else {
            $curr_page[] = $row;
        }
    }

    if ($curr_page) {
        $pages[] = $curr_page;
    }

    # Now let's actually create some pages!
    # For every page, let's figure out how big the blank image that we'll be copying into.
    $tex_file = fopen('justified.tex', 'w');
    for ($page_count = 1; $page_count <= count($pages); $page_count++) {
        $rows = $pages[$page_count - 1];
        $height = 0;
        $max_width = 0;
        foreach ($rows as $row) {
            # I think of adding margins above every row but the first. Easier that way...
            if ($height) {
                $height += $margin;
            }
            $height += $row[0]['height'];

            $curr_width = 0;
            foreach ($row as $image) {
                $curr_width += $image['width'];
            }
            $curr_width += $margin * (count($row) - 1);

            if ($curr_width > $max_width) {
                $max_width = $curr_width;
            }
        }

        $canvas = imagecreatetruecolor($max_width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        $y = 0;
        foreach ($rows as $row) {
            $x = 0;
            foreach ($row as $img_info) {

                $filename = $img_info['file'];
                if (preg_match('#\.jpe?g$#i', $filename)) {
                    $img = imagecreatefromjpeg($filename);
                } elseif(preg_match('#\.gif$#i', $filename)) {
                    $img = imagecreatefromgif($filename);
                } elseif(preg_match('#\.png$#i', $filename)) {
                    $img = imagecreatefrompng($filename);
                } else {
                    print "What's this? {$img_info['file']}\n\n";
                    exit;
                }

                imagecopyresampled($canvas, $img, $x, $y, 0, 0, $img_info['width'], $img_info['height'], $img_info['orig_width'], $img_info['orig_height']);
                $x += $img_info['width'] + $margin;
            }
            
            $y += $img_info['height'] + $margin;
        }

        imagefilter($canvas, IMG_FILTER_GRAYSCALE);
        imagejpeg($canvas, "$output_dir/page_$page_count.jpg", 100);
        imagedestroy($canvas);
        fwrite($tex_file, "\includegraphics[width=\\textwidth]{flickr/pages/page_$page_count.jpg}\n");
    }

    fclose($tex_file);
