 <?php

function parse_mbox_file($filename, $unique_only = true) { 
echo "Parsing file : $filename \n";

    $file = fopen($filename, "r");
    $from_fields = array();
    $unique_emails = array();
    $in_header = true;
    while ($line = fgets($file)) {
        if ($in_header) {
            if (preg_match("/^From:\s*(.*)$/", $line, $matches)) {
                $from_field = $matches[1];
                $email_pattern = "/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/";
                if (preg_match($email_pattern, $from_field, $email_matches)) {
                    $email = $email_matches[0];
                    $name_pattern = "/(.*)<.*>/";
                    if (preg_match($name_pattern, $from_field, $name_matches)) {
                        $name = trim($name_matches[1]);
                    } else {
                        $name = $email;
                    }
                    
                    
                    if(in_array($email, $unique_emails)) {
                    # echo "Warning : duplicate email detected ->  $email skip\n";
                     continue;
                    } 
                    
                    $unique_emails[] = $email;
                    
                    
                    $name = str_replace('"', '', $name); // remove any additional double quotes
                    $from_fields[] = array(
                        "name" => $name,
                        "email" => $email
                    );
                }
            } elseif (trim($line) === "") {
                $in_header = false;
            }
        } else {
            if (preg_match("/^From\s/", $line)) {
                $in_header = true;
            }
        }
    }
    fclose($file);
    if (!empty($from_fields)) {
        // filter unique email addresses if $unique_only is true
        if ($unique_only) {
        echo "[note] : Parsing unique emails only \n";
            $unique_emails = array_unique(array_column($from_fields, 'email'));
            $unique_fields = array_filter($from_fields, function ($field) use ($unique_emails) {
                return in_array($field['email'], $unique_emails);
            });
            return array_values($unique_fields);
        } else {
            return $from_fields;
        }
    }
    return array();
} 

function folder_has_mbox_name($folder_path) {
    $folder_name = basename($folder_path);
    return strpos($folder_name, '.mbox') !== false;
}

function parse_directory($dir_path) {
    $dir = new DirectoryIterator($dir_path);
    foreach ($dir as $fileinfo) {
    
//    print_R($fileinfo); exit;
    
        if (!$fileinfo->isDot() && $fileinfo->isDir() && folder_has_mbox_name($fileinfo->getFilename())) {
            $dir_name = $fileinfo->getBasename('.mbox');
            $output_path = $fileinfo->getPath() . DIRECTORY_SEPARATOR . $dir_name . '.csv';
            $from_fields = parse_mbox_file($fileinfo->getPathname() . DIRECTORY_SEPARATOR . 'mbox', true);
            if (!empty($from_fields)) {
                $file = fopen($output_path, "w");
                foreach ($from_fields as $fields) {
                    $fields = array_map(function($value) {
                        return str_replace('"', '', $value); // remove any additional double quotes
                    }, $fields);
                    fputcsv($file, $fields);
                }
                fclose($file);
                echo "Parsed " . $fileinfo->getPathname() . " and saved output to " . $output_path . PHP_EOL;
            } else {
                echo "No data found in " . $fileinfo->getPathname() . PHP_EOL;
            }
        }
    }
}

$dir_path = __DIR__;
echo "Parsing $dir_path \n";
parse_directory($dir_path);


echo "Complete!";

?>
