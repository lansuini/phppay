<?php


use Phinx\Seed\AbstractSeed;

class SystemAccountSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $data = [
            [
                'userName' => '二牛',
                'loginName' => 'hofa',
                'loginPwd' => '02ae4d138ebff07cb6bb0efcca8c4546a105f520',
                'status' => 'Normal',
            ],
        ];
        $posts = $this->table('system_account');
        $posts->truncate();
        $posts->insert($data)->save();
    }
}
