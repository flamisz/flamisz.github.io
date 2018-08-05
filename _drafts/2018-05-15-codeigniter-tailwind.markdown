1. codeigniter into valet
2. create public folder
  - copy index.php
  - rewrite application and system route
3. yarn init
4. yarn add gulp tailwindcss autoprefixer gulp-postcss --dev
5. yarn install
6. ./node_modules/.bin/tailwind init
7. touch gulpfile.js
  var gulp = require('gulp');

  gulp.task('css', function () {
    var postcss = require('gulp-postcss');
    var tailwindcss = require('tailwindcss');

    return gulp.src('assets/css/app.css')
      .pipe(postcss([
        tailwindcss('tailwind.js'),
        require('autoprefixer'),
      ]))
      .pipe(gulp.dest('public/css'));
  });
8. create folders and files
  - assets/css/app.css
    @tailwind preflight;
    @tailwind components;
    @tailwind utilities;
  - public/css
9. gitignore
  /node_modules
10. gulp css
11. welcome page with tailwind
