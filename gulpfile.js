const gulp = require('gulp'),
      livereload = require('gulp-livereload');

gulp.task('watch', function () {
  livereload.listen();

  gulp.watch('themes/mall/ymall/**/*.*', function (file) {
    livereload.changed(file.path);
  });
});
