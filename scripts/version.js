/* eslint-disable @typescript-eslint/no-var-requires */
const fs = require('fs');
const path = require('path');
const package = require('../package.json');

const packagePath = path.join(process.cwd(), 'package.json');
const phpPath = path.join(process.cwd(), 'plugin.php');
const bumpTypes = ['patch', 'minor', 'major'];
const fsOpts = { encoding: 'utf-8' };

const getNewVersion = () => {
	const bumpType = process.argv[2];
	let [major, minor, patch] = package.version
		.split('.')
		.map((num) => parseInt(num, 10));

	if (!bumpTypes.includes(bumpType)) {
		throw new Error(`Invalid bump type: ${bumpType}`);
	}

	if (bumpType === 'patch') {
		patch += 1;
	}

	if (bumpType === 'minor') {
		patch = 0;
		minor += 1;
	}

	if (bumpType === 'major') {
		patch = 0;
		minor = 0;
		major += 1;
	}

	return `${major}.${minor}.${patch}`;
};

(async () => {
	const previousVersion = package.version;
	const newVersion = getNewVersion();

	const newPackage = JSON.stringify(
		{
			...package,
			version: newVersion,
		},
		null,
		2
	);

	const php = await fs.promises.readFile(phpPath, fsOpts);
	const newPHP = php.replace(new RegExp(previousVersion, 'g'), newVersion);

	await Promise.all([
		fs.promises.writeFile(packagePath, newPackage, fsOpts),
		fs.promises.writeFile(phpPath, newPHP, fsOpts),
	]);

	// eslint-disable-next-line no-console
	console.log(`Version bumped to: ${newVersion}`);
})();
