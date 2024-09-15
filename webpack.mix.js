const mix = require('laravel-mix');
const { exec } = require('child_process');

mix
.sass('resources/sass/tendra-front-ltr.scss', 'public/assets/front/tendra-ltr.css')
.sass('resources/sass/axies-front-ltr.scss', 'public/assets/front/axies-ltr.css')
.sass('resources/sass/back-ltr.scss', 'public/assets/admin/app-ltr.css')
.sass('resources/sass/affiliate-ltr.scss', 'public/assets/front/affiliate-ltr.css')

.js('resources/js/tendra-front.js', 'public/assets/front/tendra.js')
.js('resources/js/axies-front.js', 'public/assets/front/axies.js')
.js('resources/js/back.js', 'public/assets/admin/app.js')
.options({ processCssUrls: false })
.sourceMaps();

exec("rtlcss public/assets/front/tendra-ltr.css ./public/assets/front/tendra-rtl.css");
exec("rtlcss public/assets/front/axies-ltr.css ./public/assets/front/axies-rtl.css");
exec("rtlcss public/assets/admin/app-ltr.css ./public/assets/admin/app-rtl.css");
exec("rtlcss public/assets/front/affiliate-ltr.css ./public/assets/front/affiliate-rtl.css");
