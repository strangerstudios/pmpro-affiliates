import { registerBlockType } from "@wordpress/blocks";
import metadata from "./block.json";
import Edit from "./edit";

registerBlockType(metadata, {
    title: metadata.title,
    attributes: metadata.attributes,
    edit: Edit,
    save: () => null
});