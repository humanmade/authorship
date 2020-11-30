/**
 * This file defines the production build configuration
 */
const { helpers, presets } = require( '@humanmade/webpack-helpers' );
const { filePath } = helpers;
const { shared } = require( './webpack.shared.js' );
const webpack = require( 'webpack' );

/** @type {webpack.Configuration} */
const config = {
	...shared,
	output: {
		path: filePath( 'build' ),
	},
};

module.exports = [
	presets.production( config ),
];
