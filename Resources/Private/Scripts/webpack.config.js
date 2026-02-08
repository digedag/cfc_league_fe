const Encore = require('@symfony/webpack-encore');

Encore
	.setOutputPath('../../Public/JS/t3sports/')
	.setPublicPath('/')

	.addEntry('t3sports', './assets/ts/index.ts')
	.addAliases({
		'@': __dirname + '/assets'
	})

	.disableSingleRuntimeChunk()
	.cleanupOutputBeforeBuild()
	.enableSourceMaps(!Encore.isProduction())
	.enableBuildNotifications()

	.enableTypeScriptLoader()
	// Vue aktivieren
	.enableVueLoader(() => {}, { version: 3 })
	.enableSassLoader((options) => {
		options.additionalData = `@use "sass:color"; @use "@/scss/variables" as *;`;
	}, {
		resolveUrlLoader: false
	})

	.configureBabel(() => {}, {
		useBuiltIns: 'entry',
		corejs: 3
	});

module.exports = Encore.getWebpackConfig();
