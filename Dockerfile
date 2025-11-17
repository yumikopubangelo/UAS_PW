# Mulai dari gambar resmi PHP 8.2 dengan Apache
FROM php:8.2-apache

# Instal ekstensi PHP yang dibutuhkan oleh proyek Anda
# XAMPP biasanya punya ini, tapi kontainer Docker harus diinstal manual
RUN docker-php-ext-install mysqli pdo pdo_mysql

# (Opsional) Mengaktifkan mod_rewrite untuk URL cantik (jika Anda pakai nanti)
RUN a2enmod rewrite