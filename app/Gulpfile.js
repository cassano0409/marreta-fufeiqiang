"use strict";

const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const clean_css = require('gulp-clean-css');
const sourcemaps = require('gulp-sourcemaps');
const imagemin = require('gulp-imagemin');
const webp = require('gulp-webp');
const newer = require('gulp-newer');
const rename = require('gulp-rename');
const ttf2woff = require('gulp-ttf2woff');
const ttf2woff2 = require('gulp-ttf2woff2');
const ttf2eot = require('gulp-ttf2eot');
const svgmin = require('gulp-svgmin');

const paths = {
    styles: {
        src: 'assets/scss/*.scss',
        dest: 'dist/css'
    },
    scripts: {
        src: 'assets/js/*.js',
        dest: 'dist/js'
    },
    images: {
        src: 'assets/images/**/*',
        dest: 'dist/images'
    },
    fonts: {
        src: 'assets/fonts/**/*.ttf',
        dest: 'dist/fonts'
    },
    icons: {
        src: 'assets/icons/**/*.svg',
        dest: 'dist/icons'
    }
};

function styles() {
    return gulp.src(paths.styles.src)
        .pipe(sourcemaps.init())
        .pipe(sass({
            outputStyle: "expanded",
            includePaths: ['./node_modules']
        }))
        .pipe(concat('style.css'))
        .pipe(clean_css())
        .pipe(gulp.dest(paths.styles.dest))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.styles.dest))
}

function scripts() {
    return gulp.src(paths.scripts.src)
        .pipe(sourcemaps.init())
        .pipe(concat('scripts.js'))
        .pipe(uglify())
        .pipe(gulp.dest(paths.scripts.dest))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.scripts.dest))
}

function images() {
    return gulp.src(paths.images.src)
        .pipe(newer(paths.images.dest))
        .pipe(imagemin())
        .pipe(gulp.dest(paths.images.dest))
        .pipe(webp())
        .pipe(gulp.dest(paths.images.dest))
}

function icons() {
    return gulp.src(paths.icons.src)
        .pipe(newer(paths.icons.dest))
        .pipe(svgmin())
        .pipe(gulp.dest(paths.icons.dest))
}

function fonts() {
    return gulp.src(paths.fonts.src)
        .pipe(newer(paths.fonts.dest))

        .pipe(ttf2woff())
        .pipe(rename({ extname: '.woff' }))
        .pipe(gulp.dest(paths.fonts.dest))

        .pipe(gulp.src(paths.fonts.src))
        .pipe(ttf2woff2())
        .pipe(rename({ extname: '.woff2' }))
        .pipe(gulp.dest(paths.fonts.dest))

        .pipe(gulp.src(paths.fonts.src))
        .pipe(ttf2eot())
        .pipe(rename({ extname: '.eot' }))
        .pipe(gulp.dest(paths.fonts.dest))

        .pipe(gulp.src(paths.fonts.src))
        .pipe(gulp.dest(paths.fonts.dest));
}

function watch() {
    gulp.watch(paths.styles.src, styles);
    gulp.watch(paths.scripts.src, scripts);
    gulp.watch(paths.images.src, images);
    gulp.watch(paths.fonts.src, fonts);
    gulp.watch(paths.icons.src, icons);
}

exports.default = gulp.series(
    gulp.parallel(styles, scripts, images, fonts, icons),
    watch
);