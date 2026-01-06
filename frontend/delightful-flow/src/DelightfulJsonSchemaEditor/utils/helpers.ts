import _ from 'lodash';
// @ts-ignore
import Snowflake from 'snowflake-id';
import { SCHEMA_TYPE, SchemaOption, getSchemaToOptionsMap } from '../constants';
import Schema from '../types/Schema';
import { commonDefaultSchema } from './SchemaUtils';
import { FormItemType } from '@/DelightfulExpressionWidget/types';

const snowflake = new Snowflake({
  mid: 1024,
  offset: (2020 - 1970) * 31536000 * 1000,
});

export const SnowflakeId = () => {
  return snowflake.generate();
};

export function cleanAndFilterArray<FilterType>(inputArray: FilterType[]): FilterType[] {
  if (!inputArray) return [];

  const allowedTypes = SCHEMA_TYPE;
	// Filter out empty strings and invalid types
  const filteredArray = _.filter(inputArray, (item) => {
    if (_.isEmpty(item)) {
		  return false; // Drop empty strings
    }
    return allowedTypes.includes(item as any);
  });

	// Remove duplicates using lodash uniqWith
  // @ts-ignore
  const uniqueArray = _.uniqWith(filteredArray, _.isEqual);

  return uniqueArray;
}

/** Generate the default root node */
export const genRootField = (
  oneChildAtLeast: boolean,
  firstChildKey: string,
) => {
  let defaultSchema = {
    ...commonDefaultSchema,
    type: 'object',
    properties: {} as Record<string, Schema>,
    required: [] as string[],
  };

  if (oneChildAtLeast) {
    defaultSchema.properties[firstChildKey] = {
      type: 'string',
      ...commonDefaultSchema,
    };
    defaultSchema.required.push(firstChildKey);
  }
  return defaultSchema;
};

// Get dropdown options for the current schema enum
export const getSelectOptions = (schemaTypes: FormItemType[]) => {
    return schemaTypes.reduce((acc, curSchemaType) => {
        const schemaTypeOptions = _.get(getSchemaToOptionsMap(), [curSchemaType], [])
        acc.push(...schemaTypeOptions)
        return acc
    }, [] as SchemaOption[])
}

/** Export schema without value fields */
export const unFocusSchemaValue = (schema: Schema) => {
	//@ts-ignore
	if(schema.value) schema.value = null
	if(schema.properties) {
		Object.values(schema.properties).forEach((subSchema) => {
			unFocusSchemaValue(subSchema)
		})
	}
}