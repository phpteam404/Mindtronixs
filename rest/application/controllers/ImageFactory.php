<?php
class ImageFactory
    {
        protected   $original;
        public      $destination;

        public  function FetchOriginal($file)
            {
                $size                       =   getimagesize($file);
                $this->original['width']    =   $size[0];
                $this->original['height']   =   $size[1];
                $this->original['type']     =   $size['mime'];
                return $this;
            }

        public  function Thumbnailer($thumb_target = '', $width = 60,$height = 60,$SetFileName = false, $quality = 80)
            {
                // Set original file settings
                // print_r($thumb_target);exit;
                $this->FetchOriginal($thumb_target);
                // Determine kind to extract from
                if($this->original['type'] == 'image/gif')
                    $thumb_img  =   imagecreatefromgif($thumb_target);
                elseif($this->original['type'] == 'image/png') {
                        $thumb_img  =   imagecreatefrompng($thumb_target);
                        $quality    =   7;
                    }
                elseif($this->original['type'] == 'image/jpeg')
                        $thumb_img  =   imagecreatefromjpeg($thumb_target);
                else
                    return false;
                // Assign variables for calculations
                $w  =   $this->original['width'];
                $h  =   $this->original['height'];
                // Calculate proportional height/width
                if($w > $h) {
                        $new_height =   $height;
                        $new_width  =   floor($w * ($new_height / $h));
                        $crop_x     =   ceil(($w - $h) / 2);
                        $crop_y     =   0;
                    }
                else {
                        $new_width  =   $width;
                        $new_height =   floor( $h * ( $new_width / $w ));
                        $crop_x     =   0;
                        $crop_y     =   ceil(($h - $w) / 2);
                    }
                // New image
                $tmp_img = imagecreatetruecolor($width,$height);
                // Copy/crop action
                imagecopyresampled($tmp_img, $thumb_img, 0, 0, $crop_x, $crop_y, $new_width, $new_height, $w, $h);
                // If false, send browser header for output to browser window
                if($SetFileName == false)
                    header('Content-Type: '.$this->original['type']);
                // Output proper image type
                if($this->original['type'] == 'image/gif')
                    imagegif($tmp_img);
                elseif($this->original['type'] == 'image/png')
                    ($SetFileName !== false)? imagepng($tmp_img, $SetFileName, $quality) : imagepng($tmp_img);
                elseif($this->original['type'] == 'image/jpeg')
                    ($SetFileName !== false)? imagejpeg($tmp_img, $SetFileName, $quality) : imagejpeg($tmp_img);
                // Destroy set images
                if(isset($thumb_img))
                    imagedestroy($thumb_img); 
                // Destroy image
                if(isset($tmp_img))
                    imagedestroy($tmp_img);
            }
    }
?>