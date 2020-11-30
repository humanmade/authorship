/**
 * This file defines the production build configuration
 */
const { helpers, presets } = require( '@humanmade/webpack-helpers' );
const { choosePort, cleanOnExit, filePath } = helpers;
const { shared } = require( './webpack.shared.js' );
const webpack = require( 'webpack' );
const webpackDevServer = require( 'webpack-dev-server' );

/** @type {webpackDevServer.Configuration} */
const devServer = {
	https: true,
	// watchOptions: {
	// 	aggregateTimeout: 200,
	// 	poll: 1000,
	// },
};

module.exports = choosePort( 8080 ).then( port => {
	/** @type {webpack.Configuration} */
	const config = {
		...shared,
		devServer : {
			...devServer,
			port,
		},
		output: {
			path: filePath( 'build' ),
			publicPath: `https://localhost:${ port }/authorship/`,
		},
	};

	return [
		presets.development( config ),
	];
} );

// Clean up manifests on exit.
cleanOnExit( [
	filePath( 'build/asset-manifest.json' ),
] );
