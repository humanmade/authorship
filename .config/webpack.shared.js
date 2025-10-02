const { externals, helpers } = require( '@humanmade/webpack-helpers' );
const { filePath } = helpers;
const webpack = require( 'webpack' );

/** @type {webpack.Configuration} */
const shared = {
	externals,
	plugins: [],
	entry: {
		main: filePath( 'src/index.tsx' ),
		style: filePath( 'src/style.scss' ),
	},
	resolve: {
		extensions: [
			'.scss',
			'.ts',
			'.tsx',
			'.js',
			'.jsx',
			'.json',
		],
	},
}

module.exports = {
	shared
};
