<?php


use Phinx\Seed\AbstractSeed;

class MerchantAmountSeeder extends AbstractSeed
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
                'merchantNo' => '88888888',
                'merchantId' => 1,
            ],
        ];
        $posts = $this->table('merchant_amount');
        $posts->truncate();
        $posts->insert($data)->save();
    }
}
