<?php

namespace App\Console\Commands;

use App\Http\Services\CameraCrawlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CameraCrawl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:crawl-camera';

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
        $cameraCrawl = new CameraCrawlService();

        $this->output->writeln('<comment>Kamera çekme işlemi başladı..</comment>');

        $cameraCrawl->initState();
        $cameraCrawl->brandCameras();

        $this->output->writeln('<info>Kameralar çekildi ve oluşturuldu</info>');
        $this->output->writeln('<comment>Kameralar insert ediliyor...</comment>');

        $cameraCrawl->insertCamerasToDB();

        $this->output->writeln('<info>Kameralar insert edildi</info>');
        $this->output->writeln('<comment>Kamera imajları insert ediliyor...</comment>');

        $cameraCrawl->insertCameraImagesToDB();

        $this->output->writeln('<info>Kamera imajları insert edildi</info>');
        $this->output->writeln('<comment>Silinen kayıtlarla bağlantılı kayıt varsa siliniyor..</comment>');

        $cameraCrawl->removeOldRecords();

        $this->output->writeln('<info>İşlem başarıyla tamamlandı.</info>');
        $this->output->writeln('<info>Toplam veri tabanı sorgu sayısı : '.count(DB::getQueryLog()).' </info>');
    }
}
