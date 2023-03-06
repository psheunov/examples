let mix = require('laravel-mix')

let postCssPlugins = [
    require("autoprefixer")(),
    require("precss")()
]

mix
  .setPublicPath('dist')
  .js('resources/js/tool.js', 'js')
  .postCss('resources/css/tool.css', 'css', postCssPlugins)
