const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		main: path.resolve( __dirname, 'src/index.tsx' ),
		style: path.resolve( __dirname, 'src/style.scss' ),
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: path.resolve( __dirname, 'build' ),
	},
};
