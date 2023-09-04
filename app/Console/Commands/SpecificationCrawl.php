<?php

namespace App\Console\Commands;

use App\Http\Services\SpecificationAndSensorCrawlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SpecificationCrawl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:crawl-spec';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::enableQueryLog();
        $specCrawler = new SpecificationAndSensorCrawlService();

        $this->output->writeln('<comment>Kamera sensör ve specifications çekme işlemi başladı..</comment>');

        [$urls, $specsList] = $specCrawler->iterate();

        $this->output->writeln('<info>Kameralar sensör ve specifications bilgileri çekildi</info>');
        $this->output->writeln('<comment>Kamera sensör ve specifications bilgileri filter/insert ediliyor...</comment>');

        $specCrawler->cameraSpecificationsAndSensor($urls, $specsList);

        $this->output->writeln('<info>Kameralar sensör ve specifications bilgileri/insert filter edildi</info>');
        $this->output->writeln('<comment>Kamera sensör ve specifications duplicate kayıtlar siliniyor...</comment>');
        //$specCrawler->insertSpecifications();
        $specCrawler->removeDuplicates();

        $this->output->writeln('<info>İşlem başarıyla tamamlandı.</info>');
        $this->output->writeln('<info>Toplam veri tabanı sorgu sayısı : '.count(DB::getQueryLog()).' </info>');
    }
}
