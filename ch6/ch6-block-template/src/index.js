import { registerBlockType } from '@wordpress/blocks';
 
registerBlockType( 'ch6bt/twitter-feed', {
    title: 'Twitter Feed',
    icon: 'twitter',
    category: 'design',
    edit: () =>
<a href="https://twitter.com/ylefebvre">Twitter Feed</a>,
    save: () =>
<a href="https://twitter.com/ylefebvre">Twitter Feed</a>,
} );
