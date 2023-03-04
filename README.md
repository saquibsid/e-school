# e-school

1. Create .env file and copy code from .env.example
2. Run composer update 
3. Create db with eschool name or whatever name you want to add,save db details in env file if anything change.
3. Run migrations
4. Create these folder
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p assets/storage
chmod -R 777 storage
4. Run Seeder
5. Run this command php artisan storage:link
6. Run the following command
php artisan cache:clear
php artisan config:clear
php artisan view:clear