import { FormItemType } from "@/DelightfulExpressionWidget/types";
import i18next, { TFunction } from "i18next";

export const JSONPATH_JOIN_CHAR = '.';

export const STRING_FORMATS = [
  { name: 'date-time' },
  { name: 'date' },
  { name: 'email' },
  { name: 'hostname' },
  { name: 'ipv4' },
  { name: 'ipv6' },
  { name: 'uri' },
];

export const MOCK_SOURCE = [
    { name: i18next.t("jsonSchema.mockString", { ns: "delightfulFlow", defaultValue: 'String' }), mock: '@string' },
    { name: i18next.t("jsonSchema.mockNatural", { ns: "delightfulFlow", defaultValue: 'Natural number' }), mock: '@natural' },
    { name: i18next.t("jsonSchema.mockFloat", { ns: "delightfulFlow", defaultValue: 'Float' }), mock: '@float' },
    { name: i18next.t("jsonSchema.mockCharacter", { ns: "delightfulFlow", defaultValue: 'Character' }), mock: '@character' },
    { name: i18next.t("jsonSchema.mockBoolean", { ns: "delightfulFlow", defaultValue: 'Boolean' }), mock: '@boolean' },
    { name: i18next.t("jsonSchema.mockUrl", { ns: "delightfulFlow", defaultValue: 'url' }), mock: '@url' },
    { name: i18next.t("jsonSchema.mockDomain", { ns: "delightfulFlow", defaultValue: 'Domain' }), mock: '@domain' },
    { name: i18next.t("jsonSchema.mockIp", { ns: "delightfulFlow", defaultValue: 'IP address' }), mock: '@ip' },
    { name: i18next.t("jsonSchema.mockId", { ns: "delightfulFlow", defaultValue: 'id' }), mock: '@id' },
    { name: i18next.t("jsonSchema.mockGuid", { ns: "delightfulFlow", defaultValue: 'guid' }), mock: '@guid' },
    { name: i18next.t("jsonSchema.mockNow", { ns: "delightfulFlow", defaultValue: 'Current time' }), mock: '@now' },
    { name: i18next.t("jsonSchema.mockTimestamp", { ns: "delightfulFlow", defaultValue: 'Timestamp' }), mock: '@timestamp' },
    { name: i18next.t("jsonSchema.mockDate", { ns: "delightfulFlow", defaultValue: 'Date' }), mock: '@date' },
    { name: i18next.t("jsonSchema.mockTime", { ns: "delightfulFlow", defaultValue: 'Time' }), mock: '@time' },
    { name: i18next.t("jsonSchema.mockDatetime", { ns: "delightfulFlow", defaultValue: 'Datetime' }), mock: '@datetime' },
    { name: i18next.t("jsonSchema.mockImage", { ns: "delightfulFlow", defaultValue: 'Image URL' }), mock: '@image' },
    { name: i18next.t("jsonSchema.mockImageData", { ns: "delightfulFlow", defaultValue: 'Image data' }), mock: '@imageData' },
    { name: i18next.t("jsonSchema.mockColor", { ns: "delightfulFlow", defaultValue: 'Color' }), mock: '@color' },
    { name: i18next.t("jsonSchema.mockHex", { ns: "delightfulFlow", defaultValue: 'Color hex' }), mock: '@hex' },
    { name: i18next.t("jsonSchema.mockRgba", { ns: "delightfulFlow", defaultValue: 'Color rgba' }), mock: '@rgba' },
    { name: i18next.t("jsonSchema.mockRgb", { ns: "delightfulFlow", defaultValue: 'Color rgb' }), mock: '@rgb' },
    { name: i18next.t("jsonSchema.mockHsl", { ns: "delightfulFlow", defaultValue: 'Color hsl' }), mock: '@hsl' },
    { name: i18next.t("jsonSchema.mockInteger", { ns: "delightfulFlow", defaultValue: 'Integer' }), mock: '@integer' },
    { name: i18next.t("jsonSchema.mockEmail", { ns: "delightfulFlow", defaultValue: 'email' }), mock: '@email' },
    { name: i18next.t("jsonSchema.mockParagraph", { ns: "delightfulFlow", defaultValue: 'Long text' }), mock: '@paragraph' },
    { name: i18next.t("jsonSchema.mockSentence", { ns: "delightfulFlow", defaultValue: 'Sentence' }), mock: '@sentence' },
    { name: i18next.t("jsonSchema.mockWord", { ns: "delightfulFlow", defaultValue: 'Word' }), mock: '@word' },
    { name: i18next.t("jsonSchema.mockCparagraph", { ns: "delightfulFlow", defaultValue: 'Long Chinese text' }), mock: '@cparagraph' },
    { name: i18next.t("jsonSchema.mockCtitle", { ns: "delightfulFlow", defaultValue: 'Chinese title' }), mock: '@ctitle' },
    { name: i18next.t("jsonSchema.mockTitle", { ns: "delightfulFlow", defaultValue: 'Title' }), mock: '@title' },
    { name: i18next.t("jsonSchema.mockName", { ns: "delightfulFlow", defaultValue: 'Name' }), mock: '@name' },
    { name: i18next.t("jsonSchema.mockCname", { ns: "delightfulFlow", defaultValue: 'Chinese name' }), mock: '@cname' },
    { name: i18next.t("jsonSchema.mockCfirst", { ns: "delightfulFlow", defaultValue: 'Chinese surname' }), mock: '@cfirst' },
    { name: i18next.t("jsonSchema.mockClast", { ns: "delightfulFlow", defaultValue: 'Chinese given name' }), mock: '@clast' },
    { name: i18next.t("jsonSchema.mockFirst", { ns: "delightfulFlow", defaultValue: 'English surname' }), mock: '@first' },
    { name: i18next.t("jsonSchema.mockLast", { ns: "delightfulFlow", defaultValue: 'English given name' }), mock: '@last' },
    { name: i18next.t("jsonSchema.mockCsentence", { ns: "delightfulFlow", defaultValue: 'Chinese sentence' }), mock: '@csentence' },
    { name: i18next.t("jsonSchema.mockCword", { ns: "delightfulFlow", defaultValue: 'Chinese phrase' }), mock: '@cword' },
    { name: i18next.t("jsonSchema.mockRegion", { ns: "delightfulFlow", defaultValue: 'Address' }), mock: '@region' },
    { name: i18next.t("jsonSchema.mockProvince", { ns: "delightfulFlow", defaultValue: 'Province' }), mock: '@province' },
    { name: i18next.t("jsonSchema.mockCity", { ns: "delightfulFlow", defaultValue: 'City' }), mock: '@city' },
    { name: i18next.t("jsonSchema.mockCounty", { ns: "delightfulFlow", defaultValue: 'County' }), mock: '@county' },
    { name: i18next.t("jsonSchema.mockUpper", { ns: "delightfulFlow", defaultValue: 'Convert to uppercase' }), mock: '@upper' },
    { name: i18next.t("jsonSchema.mockLower", { ns: "delightfulFlow", defaultValue: 'Convert to lowercase' }), mock: '@lower' },
    { name: i18next.t("jsonSchema.mockPick", { ns: "delightfulFlow", defaultValue: 'Pick (enum)' }), mock: '@pick' },
    { name: i18next.t("jsonSchema.mockShuffle", { ns: "delightfulFlow", defaultValue: 'Shuffle array' }), mock: '@shuffle' },
    { name: i18next.t("jsonSchema.mockProtocol", { ns: "delightfulFlow", defaultValue: 'Protocol' }), mock: '@protocol' },
];

export const SCHEMA_TYPE = [
    FormItemType.String,
    FormItemType.Number,
    FormItemType.Boolean,
    FormItemType.Integer,
    FormItemType.Object,
    FormItemType.Array,
];

export const ONLY_JSON_ROOT = [
    FormItemType.Object, 
    FormItemType.Array
];

// Keyboard keyCode map
export const KEY_CODE_MAP = {
  ENTER: 13,
  TAB: 9,
  C: 67,
  V: 86,
  D: 68,
  F: 70,
  X: 88,
  A: 65,
  S: 83,
  UP: 38,
  Z: 90,
  Y: 89,
  RIGHT: 39,
  DOWN: 40,
  LEFT: 37,
  COMMAND: 91,
  DELETE: 46,
  BACKSPACE: 8,
  ESC: 27,
};

/**
 * Position options when adding a new field
 */
export enum AppendPosition {
    /** Next field after the current one */
  Next = 'next',
    /** Append to the end */
  Tail = 'tail',
}

/** Gap between objects and their child fields */
export const childFieldGap = 24

/** Separator between array and its item type */
export const SchemaValueSplitor = "$$"


export interface SchemaOption {
    label: string
    value: string
}

// Map schema types to renderable options
export const getSchemaToOptionsMap: () => Record<FormItemType, SchemaOption[]> = () => ({

    
    [FormItemType.String]: [{
        label: i18next.t("jsonSchema.typeString", { ns: "delightfulFlow", defaultValue: "String" }),
        value: FormItemType.String
    }],

    [FormItemType.Boolean]: [{
        label: i18next.t("jsonSchema.typeBoolean", { ns: "delightfulFlow", defaultValue: "Boolean" }),
        value: FormItemType.Boolean
    }],

    
    [FormItemType.Number]: [{
        label: i18next.t("jsonSchema.typeNumber", { ns: "delightfulFlow", defaultValue: "Number" }),
        value: FormItemType.Number
    }],

    
    [FormItemType.Integer]: [{
        label: i18next.t("jsonSchema.typeInteger", { ns: "delightfulFlow", defaultValue: "Integer" }),
        value: FormItemType.Integer
    }],

    
    [FormItemType.Object]: [{
        label: i18next.t("jsonSchema.typeObject", { ns: "delightfulFlow", defaultValue: "Object" }),
        value: FormItemType.Object
    }],

    
    [FormItemType.Array]: [{
        label: i18next.t("jsonSchema.typeStringArray", { ns: "delightfulFlow", defaultValue: "String array" }),
        value: `${FormItemType.Array}${SchemaValueSplitor}${FormItemType.String}`
    },{
        label: i18next.t("jsonSchema.typeNumberArray", { ns: "delightfulFlow", defaultValue: "Integer array" }),
        value: `${FormItemType.Array}${SchemaValueSplitor}${FormItemType.Number}`
    },{
        label: i18next.t("jsonSchema.typeBooleanArray", { ns: "delightfulFlow", defaultValue: "Boolean array" }),
        value: `${FormItemType.Array}${SchemaValueSplitor}${FormItemType.Boolean}`
    },{
        label: i18next.t("jsonSchema.typeIntegerArray", { ns: "delightfulFlow", defaultValue: "Number array" }),
        value: `${FormItemType.Array}${SchemaValueSplitor}${FormItemType.Integer}`
    },{
        label: i18next.t("jsonSchema.typeObjectArray", { ns: "delightfulFlow", defaultValue: "Object array" }),
        value: `${FormItemType.Array}${SchemaValueSplitor}${FormItemType.Object}`
    },{
        label: i18next.t("jsonSchema.typeArray", { ns: "delightfulFlow", defaultValue: "Array" }),
        value: FormItemType.Array
    }],
})


/**
 * {
     *  [string]: 'String',
     *  [array_string]: 'String array',
     *  ...
 * }
 */
export const getFormTypeToTitle = () => Object.values(getSchemaToOptionsMap()).reduce((acc, curTypeList) => {
    curTypeList.forEach(curType => {
        acc[curType.value] = curType.label
    })
    return acc
}, {} as Record<string, string>)



export enum ShowColumns {
    /** Variable name */
    Key = 1,
    /** Display name */
    Label = 2,
    /** Variable type */
    Type = 3,
    /** Variable value */
    Value = 4,
    /** Variable description */
    Description = 5,
    /** Encryption */
    Encryption = 6,
    /** Required */
    Required = 7
}

// Show all columns by default except encryption and required
export const DefaultDisplayColumns = Object.values(ShowColumns).filter(value => value !== ShowColumns.Encryption && value !== ShowColumns.Required) as ShowColumns[]

export const DefaultColumnNames = () => ({
    [ShowColumns.Key]: i18next.t("jsonSchema.columnKeyName", { ns: "delightfulFlow", defaultValue: "Parameter name" }),
    [ShowColumns.Label]: i18next.t("jsonSchema.columnLabelName", { ns: "delightfulFlow", defaultValue: "Display name" }),
    [ShowColumns.Type]: i18next.t("jsonSchema.columnTypeName", { ns: "delightfulFlow", defaultValue: "Parameter type" }),
    [ShowColumns.Value]: i18next.t("jsonSchema.columnValueName", { ns: "delightfulFlow", defaultValue: "Parameter value" }),
    [ShowColumns.Description]: i18next.t("jsonSchema.columnDescName", { ns: "delightfulFlow", defaultValue: "Parameter description" }),
    [ShowColumns.Encryption]: i18next.t("jsonSchema.columnEncryptionName", { ns: "delightfulFlow", defaultValue: "Encrypted" }),
    [ShowColumns.Required]: i18next.t("jsonSchema.columnRequiredName", { ns: "delightfulFlow", defaultValue: "Required" })
})


