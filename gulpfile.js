const {src, dest, series, watch, parallel} = require('gulp');
const sass = require('gulp-sass')(require('node-sass'));
const rename = require('gulp-rename');
const bs = require('browser-sync').create();
const npmDist = require('gulp-npm-dist');
const htmlInjector = require('bs-html-injector');

sass.compiler = require('node-sass');

// Compile scss files to style.css file
function compileStyle() {
    return src('./resources/scss/app.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(sass({outputStyle: 'compressed'}))
        .pipe(rename({suffix: '.min'}))
        .pipe(dest('./public/assets/css'))
        .pipe(bs.stream());
}

// Compile skins styles to css folder
function compileSkinStyle() {
    return src('./resources/scss/skins/*.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(rename({prefix: 'skin.'}))
        .pipe(dest('./public/assets/css'))
        .pipe(bs.stream());
}

// Compile pages styles to css folder
function compilePageStyle() {
    return src('./resources/scss/pages/*.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(rename({prefix: 'app.'}))
        .pipe(dest('./public/assets/css'))
        .pipe(bs.stream());
}

//Copy image media data
function copyImage() {
    return src('./resources/img/**/*.*')
        .pipe(dest('./public/assets/img'))
        .pipe(bs.stream());
}

//Copy image media data
function copyJavaScript() {
    return src('./resources/js/**/*.*')
        .pipe(dest('./public/assets/js'))
        .pipe(bs.stream());
}

//Copy image media data
function copyFonts() {
    return src('./resources/fonts/**/*.*')
        .pipe(dest('./public/assets/fonts'))
        .pipe(bs.stream());
}

// Copy dependencies to template/lib
function npmDep() {
    return src(npmDist(), {base: './node_modules/'})
        .pipe(rename(function (path) {
            path.dirname = path.dirname.replace(/\/dist/, '').replace(/\\dist/, '');
        }))
        .pipe(dest('./public/plugins'));
}

// Start a server
function serve() {
    bs.use(htmlInjector, {
        files: "**/*.html"
    });

    // Now init the Browsersync server
    bs.init({
        injectChanges: true,
        server: true
    });

    // Listen to change events on HTML and reload
    watch('**/*.html').on('change', htmlInjector);

    // Provide a callback to capture ALL events to CSS
    // files - then filter for 'change' and reload all
    // css files on the page.
    watch('scss/**/*.scss', series(compileStyle, minifyStyle));

    watch('scss/skins/**/*.scss', compileSkinStyle);
    watch('scss/pages/*.scss', compilePageStyle);

    watch(
        ['scss/_variables.scss', 'scss/bootstrap/_variables.scss'],
        series(compileStyle, compilePageStyle)
    );

}

function compileHotReload() {
    watch('resources/scss/**/*.scss', compileStyle);
    watch('resources/scss/skins/**/*.scss', compileSkinStyle);
    watch('resources/scss/pages/*.scss', compilePageStyle);
    watch(
        ['resources/scss/_variables.scss',
            'resources/scss/bootstrap/_variables.scss'],
        series(compileStyle, compilePageStyle)
    );
}

exports.serve = serve;
exports.npmDep = npmDep;
exports.compileStyle = compileStyle;
exports.compileSkinStyle = compileSkinStyle;
exports.compilePageStyle = compilePageStyle;
exports.copyJavaScript = copyJavaScript;
exports.copyImage = copyImage;
exports.copyFonts = copyFonts;
exports.develop = parallel(npmDep, series(compileStyle, compilePageStyle, compileSkinStyle, copyImage, copyFonts, copyJavaScript));
exports.watch = compileHotReload;
