import { registerPlugin } from '@wordpress/plugins';

import { name, settings } from './plugin';

// @ts-ignore
import './style.scss';

registerPlugin( name, settings );
