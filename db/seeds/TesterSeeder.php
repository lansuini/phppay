<?php


use Phinx\Seed\AbstractSeed;

class TesterSeeder extends AbstractSeed
{
    public function getDependencies()
    {
        return [
            'ChannelMerchantSeeder',
            'ChannelMerchantRateSeeder',
        ];
    }

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

    }
}
