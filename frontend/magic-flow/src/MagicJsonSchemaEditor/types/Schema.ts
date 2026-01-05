import { DataSourceOption } from '@/common/BaseUI/DropdownRenderer/Reference';
import { FormItemType, InputExpressionValue } from '@/MagicExpressionWidget/types';

export const enum EXPRESSION_ITEM_TYPE {
  normal = 'fields',
  input = 'input',
  func = 'methods',
}


export default interface Schema {
  // Field title
  title?: string;
  // Field type
  type: string;
  // Field properties
  properties?: Record<string, Schema>;
  // Required fields
  required?: string[];
  // Field description
  description?: string;
  // Child schema
  items?: Schema;
  // Field value
  value: InputExpressionValue;
  // Whether the field is encrypted
  encryption?: boolean
  // Encrypted value
  encryption_value?: string
  // Field sort order
  sort?: number

  // Fields currently unused
  default?: boolean | string;
  mock?: string | { mock: string };
  minLength?: number;
  maxLength?: number;
  minimum?: number;
  maximum?: number;
  pattern?: string;
  enum?: string[] | number[];
  enumDesc?: string;
  format?: string;
  exclusiveMinimum?: boolean;
  exclusiveMaximum?: boolean;
  minItems?: number;
  maxItems?: number;
  uniqueItems?: boolean;
}

export interface CustomOptions {
  items?: FormItemType[];
  normal?: FormItemType[];
  root?: FormItemType[];
}

/** Enumeration of fields that can be disabled */
export enum DisabledField {
  /** Field name */
  Name = 'key',
  Title = 'title',
  Description = 'description',
  Type = 'type',
  Required = 'required',
  Encryption = 'encryption',
}

/** Visibility for custom field configuration */
export type CustomFieldsConfig = {
	onlyExpression?: boolean
	constantsDataSource?: DataSourceOption[]
	allowOperation?: boolean
	onlyConstants?: boolean
	allowAdd?: boolean
}