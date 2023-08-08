# Camera Specifications and Categorization

Bu Laravel projesi, çeşitli kameraların teknik özelliklerini görüntüleyen, kontrol eden bir altyapıyı oluşturmayı amaçlamaktadır.

## Proje Açıklaması

Bu projenin amacı, farklı kamera modellerinin teknik özelliklerini çekerek, bu özellikleri kategorilere ayırarak ve sunarak, kullanıcıların ihtiyaç duyduğu bilgilere hızlıca erişmelerini sağlamaktır.

Veriler, aşağıdaki kaynaktan alınmaktadır:
[https://www.digicamdb.com/cameras/](https://www.digicamdb.com/cameras/)

## Özellikler

- **Veri Toplama ve Saklama:** Proje, belirtilen kaynaktan kamera teknik özelliklerini çeker ve veritabanında saklar. Bu sayede kullanıcılar, verilere hızlıca erişebilir.
- **Üretici Tabanlı Görüntüleme:** Kullanıcılar, farklı üreticilerin kameralarına göz atabilirler. Örneğin, Canon marka kameralar, Nikon marka kameralar gibi üreticilerin ürün yelpazesine erişebilirler.
- **Kamera Detay Sayfası:** Her kamera modeli için ayrıntılı bir detay sayfası bulunmaktadır. Kullanıcılar, seçtikleri kamera modelinin daha geniş teknik özelliklerini, resimlerini ve ilgili bilgileri inceleyebilirler.
- **Arama Seçenekleri:** Kullanıcılar, kameraları isme göre arayabilir. Bu, istedikleri isme sahip kameraları hızla bulmalarını sağlar.

## Kolay Yönetim ve Bakım

Projemiz, yönetim ve bakım süreçlerini basit ve etkili bir şekilde destekler. Aşağıdaki özellikler, projenin yönetimini ve bakımını daha da kolaylaştırır:

1. **Veri CRUD İşlemleri:** Projede ekleme, silme, düzenleme gibi aksiyonlar için routelar (yollar) sağlanmıştır.

2. **Veri Güncellemeleri İçin Otomatik Komutlar:** Projede kullanılan verileri güncellemek ve senkronize etmek için özel komutlar sunulmaktadır. Bu komutlar, projenin kullandığı kaynaktan otomatik olarak veri çekerek, veritabanını güncellemeyi sağlar.

3. **Veri Senkronizasyonu:** Projede yer alan komutlar sayesinde, kaynaktaki verilerle projedeki verileri senkronize etmek kolaydır. Bu, güncel ve doğru verilere erişimin sağlanmasını sağlar.

4. **Veritabanı Yönetimi:** Projenin veritabanını güncellemek veya yönetmek için gerekli routelar (yolllar) sunulmaktadır. Bu sayede veritabanındaki kamera modelleri ve özellikleri yönetilirken güvenilir bir yöntem sunulur.

Bu özellikler, projenin yönetimini ve bakımını daha erişilebilir ve kullanıcı dostu hale getirerek, projeyi genişletmek veya verileri güncellemek isteyen geliştiricilere kolaylık sağlar.

## Gereksinimler

- PHP 8.1 veya daha üstü
- Laravel 10.0 veya daha üstü
- MySQL veya diğer bir veritabanı yönetim sistemi

## Kurulum

Bu bölümde, projeyi yerel bir geliştirme ortamında çalıştırmak için adımları bulabilirsiniz.

1. Projeyi klonlayın:

   ```bash
   git clone https://github.com/kullanici/ProjeAdi.git

2. Proje dizinine gidin:
   ```bash
   cd ProjeAdi

4. Gerekli bağımlılıkları yükleyin:

    ```bash
    composer install

5. .env dosyasını oluşturun ve veritabanı ayarlarını yapın:
    ```bash
    cp .env.example .env
    php artisan key:generate

6. Veritabanını oluşturun ve migrate işlemini gerçekleştirin:
    ```bash
    php artisan migrate

7. Projeyi çalıştırın:
    ```bash
    php artisan serve
    
8. Tarayıcınızı açın ve http://127.0.0.1:8000 adresine giderek uygulamayı görüntüleyin.
