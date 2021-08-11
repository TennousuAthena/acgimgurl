<?php
if(!extension_loaded("sqlite3")){
    exit("Sqlite Extension Does Not Exist");
}
class AcgDB extends SQLite3
{
    /**
     * @param string $file
     */
    public function __construct($file = "acgimg.db")
    {
        $this->open($file);
    }

    /**
     * @return array|false
     * @throws Exception
     */
    public function get(): array{
        try {
            $sql = "SELECT * FROM `acgimg` ORDER BY RANDOM() limit 1";
            $result = $this->query($sql);
        }catch (Exception $e){
            throw new Exception("数据错误");
        }
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function update(int $id, array $data): bool
    {
        try {
            $json = json_encode($data);
            $sql =
                <<<EOF
UPDATE `acgimg` SET info = '$json' WHERE id = $id
EOF;
            $this->exec($sql);
        }catch (Exception $e){
            throw new Exception("数据写入错误");
        }
        return true;
    }
}

$db = new AcgDB();
try {
    $data = $db->get();
} catch (Exception $e) {
    exit("Database error:".$e);
}
if(!$data){
    exit("Database error: ".$db->lastErrorMsg());
}

$returnType = $_GET['type'];
switch ($returnType) {
    //浏览器直接输出图片
    case 'img':
        $img = file_get_contents($data['url'], true);
        header("Content-Type: image/jpeg;");
        echo $img;
        break;
    //JSON格式输出
    case 'json':
        if($data['info'] != ""){
            $info = json_decode($data['info']);
        }else{
            $Imginfo = getimagesize($data['url']);
            $info[0] = $Imginfo[0];
            $info[1] = $Imginfo[1];
            try {
                $db->update($data['id'], $info);
            } catch (Exception $e) {
                exit(json_encode(["status"=> -1, "info"=> $e]));
            }
        }
        $json['width'] = $info[0];
        $json['height'] = $info[1];

        $json['imgurl'] = $data['url'];

        header('Content-type:text/json');
        echo json_encode($json,JSON_PRETTY_PRINT);
        break;
    //直接跳转
    default:
        header("Location:" . $data['url']);
        break;
}

$db->close();
