/**
 * This file defines the production build configuration
 */
const { helpers, presets } = require( '@humanmade/webpack-helpers' );
const { filePath } = helpers;
const { shared } = require( './webpack.shared.js' );
const webpack = require( 'webpack' );
const { WebpackManifestPlugin } = require( 'webpack-manifest-plugin' );

/** @type {webpack.Configuration} */
const config = {
	...shared,
	output: {
		path: filePath( 'build' ),
		clean: true
	},
	plugins: [
		...shared.plugins,
		new WebpackManifestPlugin( {
				fileName: 'asset-manifest.json',
		} ),
	],
};

module.exports = [
	presets.production( config ),
];
