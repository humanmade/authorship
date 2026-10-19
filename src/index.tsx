import { registerPlugin } from '@wordpress/plugins';

import { name, settings } from './plugin';

// @ts-ignore
import './index.scss';

registerPlugin( name, settings );
