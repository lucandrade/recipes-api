<?php

use Illuminate\Database\Seeder;

class UrlTableSeeder extends Seeder
{
    protected $db;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = $this->getUrlList();
        $this->db = \DB::table('rec_urls');
        array_map(function ($url) {
            if ($url) {
                $this->insertUrl($url);
            }
        }, $data);
    }

    protected function getUrlList()
    {
        $file = database_path('seeds/urls.txt');
        if (!file_exists($file)) {
            return [];
        }

        $fp = fopen($file, "r");
        $list = [];

        while(!feof($fp)) {
            array_push($list, fgets($fp));
        }

        fclose($fp);
        return $list;
    }

    protected function insertUrl($url)
    {
        $url = trim($url);
        $imported = $this->db->where('url', $url)->first();

        if (!$imported) {
            $this->db->insert(['url' => $url]);
        }

        return;
    }
}
