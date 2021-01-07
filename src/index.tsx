import { name, settings } from './plugin';

declare const wp: any;

const registerPlugin = wp.plugins.registerPlugin;

registerPlugin( name, settings );
