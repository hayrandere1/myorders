

terminale "symfony serve" kodu ile proje çalışır.

.env dosyasına veritabanı bilgilerini girmeniz gerekmektedir.

database oluşturmamışsanız; php bin/console doctrine:database:create veritanaını oluşturur

db oluşturduktan sonra; php bin/console doctrine:schema:create tabloları getirir

3 kullanıcı 1 admin oluşturmak için; php bin/console app:create-user

Api işlemleri postman 

kategori, ürün, stok işlemlerini admin tarafından yapılmaktadır,

sipariş işlemlerini user yapar

*** Docker kurumu yapıldı fakat dockerda token almada userı tanımadığı için problem yaşadım.


