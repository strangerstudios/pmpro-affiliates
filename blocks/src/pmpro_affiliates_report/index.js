import { registerBlockType } from "@wordpress/blocks";
import metadata from "./block.json";
import Edit from "./edit";

registerBlockType(metadata, {
    title: metadata.title,
    description: metadata.description,
    attributes: metadata.attributes,
    icon: metadata.icon,
    category: metadata.category,
    edit: Edit,
    save: () => null
});