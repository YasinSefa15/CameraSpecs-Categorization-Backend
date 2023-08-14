<?php

namespace App\Console\Commands;

use App\Http\Services\BrandCrawlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BrandCrawl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:crawl-brand';

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
        $brandCrawl = new BrandCrawlService();

        $this->output->writeln('<comment>Marka bilgileri ve imajlar çekiliyor...</comment>');

        $brandCrawl->getBrandsAndImages();

        $this->output->writeln('<info>Marka bilgileri ve imajlar çekildi</info>');
        $this->output->writeln('<comment>Marka bilgileri insert ediliyor ve duplicateler siliniyor...</comment>');

        $brandCrawl->insertBrandsAndRemoveDuplicates();

        $this->output->writeln('<info>Marka bilgileri insert edildi ve duplicateler silindi</info>');
        $this->output->writeln('<comment>Marka imajları insert ediliyor ve duplicateler siliniyor...</comment>');

        $brandCrawl->insertImagesAndRemoveDuplicates();

        $this->output->writeln('<info>İşlem başarıyla tamamlandı.</info>');

        $this->output->writeln('<info>Toplam veri tabanı sorgu sayısı: '.count(DB::getQueryLog()).'</info>');
    }
}
