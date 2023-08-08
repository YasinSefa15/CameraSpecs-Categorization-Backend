# Camera Specifications and Categorization

Bu Laravel projesi, çeşitli kameraların teknik özelliklerini kategorize eden bir altyapıyı oluşturmayı amaçlamaktadır.

## Proje Açıklaması

Bu projenin amacı, farklı kamera modellerinin teknik özelliklerini çekerek, bu özellikleri kategorilere ayırarak ve sunarak, kullanıcıların ihtiyaç duyduğu bilgilere hızlıca erişmelerini sağlamaktır.

Veriler, aşağıdaki kaynaktan alınmaktadır:
[https://www.digicamdb.com/cameras/](https://www.digicamdb.com/cameras/)

## Özellikler

- Farklı kamera modellerinin teknik özelliklerinin toplanması.
- Teknik özelliklerin kategorize edilmesi ve saklanması.

## Gereksinimler

- PHP 8.1 veya daha üstü
- Laravel 10.0 veya daha üstü
- MySQL veya diğer bir veritabanı yönetim sistemi


## Kurulum

Bu bölümde, projeyi yerel bir geliştirme ortamında çalıştırmak için adımları bulabilirsiniz.

1. Projeyi klonlayın:

   ```bash
   git clone https://github.com/kullanici/ProjeAdi.git

   Proje dizinine gidin:
   ```bash
   cd ProjeAdi

Gerekli bağımlılıkları yükleyin:

    ```bash
    composer install

    .env dosyasını oluşturun ve veritabanı ayarlarını yapın:
    ```bash
    cp .env.example .env
    php artisan key:generate

    Veritabanını oluşturun ve migrate işlemini gerçekleştirin:

    ```bash
    php artisan migrate

    Projeyi çalıştırın:
    ```bash
    php artisan serve
    Tarayıcınızı açın ve http://127.0.0.1:8000 adresine giderek uygulamayı görüntüleyin.
