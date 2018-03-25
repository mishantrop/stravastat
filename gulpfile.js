var gulp = require('gulp');
var browserSync = require('browser-sync').create();
var sass = require('gulp-sass');
var concat = require('gulp-concat');
var uglify = require('gulp-uglifyjs');
var rename = require('gulp-rename');
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('gulp-autoprefixer');

var templateMainPath = 'assets/';
var reload = browserSync.reload;

gulp.task('sass-main', function() {
    return gulp.src(templateMainPath + 'sass/main.scss')
        .pipe(sass({outputStyle: 'compressed'}))
        .pipe(rename('main.min.css'))
        .pipe(gulp.dest(templateMainPath + 'css'))
        .pipe(reload({
            stream: true
        }))
		.pipe(browserSync.stream());
});
gulp.task('js-main', function() {
    return gulp.src([
            templateMainPath + 'js/src/*.js',
        ])
        .pipe(concat('main.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(templateMainPath + 'js'))
        .pipe(reload({
            stream: true
        }))
		.pipe(browserSync.stream());
});

gulp.task('watch-main', ['sass-main', 'js-main'], function() {
    gulp.watch(templateMainPath + 'sass/*.scss', ['sass-main']);
    gulp.watch(templateMainPath + 'js/src/*.js', ['js-main']);
});

gulp.task('sync', ['sass-main', 'js-main'], function() {
    browserSync.init({
		proxy: 'http://stravastat.local/test',
        host: 'stravastat.local',
    });
	gulp.watch(templateMainPath + 'sass/*.scss', ['sass-main']);
    gulp.watch(templateMainPath + 'js/*.js', ['js-main']);
    browserSync.reload();
});

gulp.task('default', ['sync']);
gulp.task('watch', ['watch-main']);