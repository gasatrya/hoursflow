'use strict';
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );
const { spawnSync } = require( 'child_process' );

const ROOT = path.resolve( __dirname, '..' );
const FIXED_COPYRIGHT = '# Copyright (C) 2026 OpenNow contributors';
const FIXED_GENERATOR = 'X-Generator: WP-CLI i18n-command 3.0.1';

function parseOutput( argumentsList ) {
	const outputIndex = argumentsList.indexOf( '--output' );
	if ( outputIndex !== -1 && argumentsList[ outputIndex + 1 ] ) {
		return path.resolve( ROOT, argumentsList[ outputIndex + 1 ] );
	}

	const outputOption = argumentsList.find( ( argument ) =>
		argument.startsWith( '--output=' )
	);
	if ( outputOption ) {
		return path.resolve( ROOT, outputOption.slice( '--output='.length ) );
	}

	return path.join( ROOT, 'languages', 'opennow.pot' );
}

function normalizePot( contents ) {
	const lines = contents.replace( /\r\n/g, '\n' ).split( '\n' );
	if ( lines.length > 0 && /^# Copyright \(C\) \d{4}/.test( lines[ 0 ] ) ) {
		lines[ 0 ] = FIXED_COPYRIGHT;
	}

	for ( let index = 0; index < lines.length; index += 1 ) {
		if ( lines[ index ].startsWith( '"POT-Creation-Date:' ) ) {
			lines[ index ] = '"POT-Creation-Date: 1970-01-01 00:00+0000\\n"';
		}
		if ( lines[ index ].startsWith( '"X-Generator:' ) ) {
			lines[ index ] = `"${ FIXED_GENERATOR }\\n"`;
		}
	}

	return `${ lines.join( '\n' ).replace( /\n+$/, '' ) }\n`;
}

function makePot( outputPath ) {
	const destination =
		outputPath || path.join( ROOT, 'languages', 'opennow.pot' );
	fs.mkdirSync( path.dirname( destination ), { recursive: true } );

	const wpExecutable = path.join( ROOT, 'vendor', 'bin', 'wp' );
	const wpCommand = process.platform === 'win32' ? 'bash' : wpExecutable;
	const argumentsList = [
		'i18n',
		'make-pot',
		ROOT,
		destination,
		'--slug=opennow',
		'--domain=opennow',
		'--include=opennow.php,uninstall.php,src,assets',
		'--exclude=src/blocks/cta/*.test.js',
		'--skip-audit',
		'--headers={"Report-Msgid-Bugs-To":"https://github.com/gasatrya/opennow/issues"}',
		'--no-color',
	];
	if ( process.platform === 'win32' ) {
		argumentsList.unshift( wpExecutable );
	}
	const result = spawnSync( wpCommand, argumentsList, {
		cwd: ROOT,
		encoding: 'utf8',
		stdio: 'inherit',
	} );
	if ( result.error ) {
		throw result.error;
	}
	if ( result.status !== 0 ) {
		throw new Error(
			`WP-CLI i18n make-pot failed with exit code ${ result.status }.`
		);
	}

	const normalized = normalizePot( fs.readFileSync( destination, 'utf8' ) );
	fs.writeFileSync( destination, normalized, 'utf8' );
	return destination;
}

if ( require.main === module ) {
	try {
		makePot( parseOutput( process.argv.slice( 2 ) ) );
	} catch ( error ) {
		console.error( error.message );
		process.exitCode = 1;
	}
}

module.exports = { makePot, normalizePot, parseOutput };
