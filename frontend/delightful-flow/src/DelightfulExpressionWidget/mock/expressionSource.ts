export default [
    {
        "title": "Functions",
        "key": "methods_67282ed317551",
        "nodeId": "",
        "nodeType": "",
        "children": [
            {
                "title": "Built-in Functions",
                "key": "78162069390d96b9230a2f222f902b54",
                "nodeId": "",
                "nodeType": "",
                "children": [
                    {
                        "title": "Calculate the MD5 hash of a string",
                        "key": "md5",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "type": "string",
                                "desc": "String to hash."
                            },
                            {
                                "name": "binary",
                                "type": "boolean",
                                "desc": "If the optional binary flag is set to true，the MD5 digest is returned as a 16-character raw binary string."
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "Calculate the MD5 hash of a string",
                        "isMethod": true
                    },
                    {
                        "title": "mt_rand",
                        "key": "mt_rand",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "min",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "max",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "round",
                        "key": "round",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "int|float"
                            },
                            {
                                "name": "precision",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "mode",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "Check whether the string contains the given substring",
                        "key": "str_contains",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "haystack",
                                "type": "string",
                                "desc": "String to search in."
                            },
                            {
                                "name": "needle",
                                "type": "string",
                                "desc": "Substring to search for in haystack."
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "Performs a case-sensitive check，Indicates whether the needle is contained in the haystack.",
                        "isMethod": true
                    },
                    {
                        "title": "time",
                        "key": "time",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "Generate a unique ID",
                        "key": "uniqid",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "prefix",
                                "desc": "Useful when IDs might be generated in the same microsecond across hosts.\\n prefixis empty，Result length is 13 when prefix is empty; if more_entropy is true，the result length is 23.",
                                "type": "string"
                            },
                            {
                                "name": "more_entropy",
                                "desc": "If set to true，uniqid() adds extra entropy to the end of the returned string（using a linear congruential generator）。 making the unique ID even more unique.",
                                "type": "bool"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "Generate a unique ID",
                        "isMethod": true
                    },
                    {
                        "title": "str_replace",
                        "key": "str_replace",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "search",
                                "desc": "",
                                "type": "array|string"
                            },
                            {
                                "name": "replace",
                                "desc": "",
                                "type": "array|string"
                            },
                            {
                                "name": "subject",
                                "desc": "",
                                "type": "array|string"
                            },
                            {
                                "name": "count",
                                "desc": ""
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "mb_strlen",
                        "key": "mb_strlen",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "encoding",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "mb_str_pad",
                        "key": "mb_str_pad",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "length",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "pad_string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "pad_type",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "encoding",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_rand",
                        "key": "array_rand",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "num",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "is_array",
                        "key": "is_array",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "value",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "explode",
                        "key": "explode",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "separator",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "limit",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "json_encode",
                        "key": "json_encode",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "value",
                                "desc": "",
                                "type": "mixed"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "depth",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "json_decode",
                        "key": "json_decode",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "json",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "associative",
                                "desc": "",
                                "type": "bool"
                            },
                            {
                                "name": "depth",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "mb_substr",
                        "key": "mb_substr",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "start",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "length",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "encoding",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "preg_match",
                        "key": "preg_match",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "pattern",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "subject",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "matches",
                                "desc": ""
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "offset",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "preg_replace",
                        "key": "preg_replace",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "pattern",
                                "desc": "",
                                "type": "array|string"
                            },
                            {
                                "name": "replacement",
                                "desc": "",
                                "type": "array|string"
                            },
                            {
                                "name": "subject",
                                "desc": "",
                                "type": "array|string"
                            },
                            {
                                "name": "limit",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "count",
                                "desc": ""
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "preg_match_all",
                        "key": "preg_match_all",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "pattern",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "subject",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "matches",
                                "desc": ""
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "offset",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "preg_split",
                        "key": "preg_split",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "pattern",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "subject",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "limit",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "str_repeat",
                        "key": "str_repeat",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "times",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "str_split",
                        "key": "str_split",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "length",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_column",
                        "key": "array_column",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "column_key",
                                "desc": "",
                                "type": "string|int|null"
                            },
                            {
                                "name": "index_key",
                                "desc": "",
                                "type": "string|int|null"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_keys",
                        "key": "array_keys",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "filter_value",
                                "desc": "",
                                "type": "mixed"
                            },
                            {
                                "name": "strict",
                                "desc": "",
                                "type": "bool"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_values",
                        "key": "array_values",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_merge",
                        "key": "array_merge",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "arrays",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_diff",
                        "key": "array_diff",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "arrays",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_intersect",
                        "key": "array_intersect",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "arrays",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_unique",
                        "key": "array_unique",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "implode",
                        "key": "implode",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "separator",
                                "desc": "",
                                "type": "array|string"
                            },
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "count",
                        "key": "count",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "value",
                                "desc": "",
                                "type": "Countable|array"
                            },
                            {
                                "name": "mode",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "in_array",
                        "key": "in_array",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "needle",
                                "desc": "",
                                "type": "mixed"
                            },
                            {
                                "name": "haystack",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "strict",
                                "desc": "",
                                "type": "bool"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_search",
                        "key": "array_search",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "needle",
                                "desc": "",
                                "type": "mixed"
                            },
                            {
                                "name": "haystack",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "strict",
                                "desc": "",
                                "type": "bool"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_flip",
                        "key": "array_flip",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_reverse",
                        "key": "array_reverse",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "preserve_keys",
                                "desc": "",
                                "type": "bool"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_slice",
                        "key": "array_slice",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "offset",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "length",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "preserve_keys",
                                "desc": "",
                                "type": "bool"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_splice",
                        "key": "array_splice",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "offset",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "length",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "replacement",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "date",
                        "key": "date",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "format",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "timestamp",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "strtotime",
                        "key": "strtotime",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "datetime",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "baseTimestamp",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "microtime",
                        "key": "microtime",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "as_float",
                                "desc": "",
                                "type": "bool"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "strpos",
                        "key": "strpos",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "haystack",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "needle",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "offset",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "strlen",
                        "key": "strlen",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "substr",
                        "key": "substr",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "offset",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "length",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "ltrim",
                        "key": "ltrim",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "characters",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "rtrim",
                        "key": "rtrim",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "characters",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "trim",
                        "key": "trim",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "characters",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "strtolower",
                        "key": "strtolower",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "strtoupper",
                        "key": "strtoupper",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "str_starts_with",
                        "key": "str_starts_with",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "haystack",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "needle",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "str_ends_with",
                        "key": "str_ends_with",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "haystack",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "needle",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "str_pad",
                        "key": "str_pad",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "length",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "pad_string",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "pad_type",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "is_numeric",
                        "key": "is_numeric",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "value",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "is_string",
                        "key": "is_string",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "value",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "sprintf",
                        "key": "sprintf",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "format",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "values",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_count_values",
                        "key": "array_count_values",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_fill",
                        "key": "array_fill",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "start_index",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "count",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "value",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_fill_keys",
                        "key": "array_fill_keys",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "keys",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "value",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_filter",
                        "key": "array_filter",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "callback",
                                "desc": "",
                                "type": "callable"
                            },
                            {
                                "name": "mode",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_map",
                        "key": "array_map",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "callback",
                                "desc": "",
                                "type": "callable"
                            },
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "arrays",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_reduce",
                        "key": "array_reduce",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "callback",
                                "desc": "",
                                "type": "callable"
                            },
                            {
                                "name": "initial",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_replace",
                        "key": "array_replace",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "replacements",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_replace_recursive",
                        "key": "array_replace_recursive",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "replacements",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_shift",
                        "key": "array_shift",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_unshift",
                        "key": "array_unshift",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "values",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_walk_recursive",
                        "key": "array_walk_recursive",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "object|array"
                            },
                            {
                                "name": "callback",
                                "desc": "",
                                "type": "callable"
                            },
                            {
                                "name": "arg",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "shuffle",
                        "key": "shuffle",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "sort",
                        "key": "sort",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "rsort",
                        "key": "rsort",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "asort",
                        "key": "asort",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "arsort",
                        "key": "arsort",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "ksort",
                        "key": "ksort",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "krsort",
                        "key": "krsort",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "uasort",
                        "key": "uasort",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "callback",
                                "desc": "",
                                "type": "callable"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "uksort",
                        "key": "uksort",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "callback",
                                "desc": "",
                                "type": "callable"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "usort",
                        "key": "usort",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "callback",
                                "desc": "",
                                "type": "callable"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "reset",
                        "key": "reset",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "object|array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "end",
                        "key": "end",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "object|array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "next",
                        "key": "next",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "object|array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "prev",
                        "key": "prev",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "object|array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "current",
                        "key": "current",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "object|array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "key",
                        "key": "key",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "object|array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "extract",
                        "key": "extract",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "flags",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "prefix",
                                "desc": "",
                                "type": "string"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "compact",
                        "key": "compact",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "var_name",
                                "desc": ""
                            },
                            {
                                "name": "var_names",
                                "desc": ""
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_change_key_case",
                        "key": "array_change_key_case",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "case",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_chunk",
                        "key": "array_chunk",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "length",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "preserve_keys",
                                "desc": "",
                                "type": "bool"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_combine",
                        "key": "array_combine",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "keys",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "values",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_pop",
                        "key": "array_pop",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "array_push",
                        "key": "array_push",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "array",
                                "desc": "",
                                "type": "array"
                            },
                            {
                                "name": "values",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "abs",
                        "key": "abs",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "int|float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "ceil",
                        "key": "ceil",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "int|float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "floor",
                        "key": "floor",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "int|float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "sqrt",
                        "key": "sqrt",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "pow",
                        "key": "pow",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "mixed"
                            },
                            {
                                "name": "exponent",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "exp",
                        "key": "exp",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "log",
                        "key": "log",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "float"
                            },
                            {
                                "name": "base",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "log10",
                        "key": "log10",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "sin",
                        "key": "sin",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "cos",
                        "key": "cos",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "tan",
                        "key": "tan",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "asin",
                        "key": "asin",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "acos",
                        "key": "acos",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "atan",
                        "key": "atan",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "atan2",
                        "key": "atan2",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "y",
                                "desc": "",
                                "type": "float"
                            },
                            {
                                "name": "x",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "pi",
                        "key": "pi",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "fmod",
                        "key": "fmod",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "num1",
                                "desc": "",
                                "type": "float"
                            },
                            {
                                "name": "num2",
                                "desc": "",
                                "type": "float"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "rand",
                        "key": "rand",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "min",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "max",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "mt_srand",
                        "key": "mt_srand",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "seed",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "mode",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "random_int",
                        "key": "random_int",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "min",
                                "desc": "",
                                "type": "int"
                            },
                            {
                                "name": "max",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "random_bytes",
                        "key": "random_bytes",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "length",
                                "desc": "",
                                "type": "int"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "json_last_error",
                        "key": "json_last_error",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "json_last_error_msg",
                        "key": "json_last_error_msg",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "serialize",
                        "key": "serialize",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "value",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "unserialize",
                        "key": "unserialize",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "data",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "options",
                                "desc": "",
                                "type": "array"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "var_dump",
                        "key": "var_dump",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "value",
                                "desc": "",
                                "type": "mixed"
                            },
                            {
                                "name": "values",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "print_r",
                        "key": "print_r",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "value",
                                "desc": "",
                                "type": "mixed"
                            },
                            {
                                "name": "return",
                                "desc": "",
                                "type": "bool"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    },
                    {
                        "title": "printf",
                        "key": "printf",
                        "nodeId": "",
                        "nodeType": "",
                        "children": [],
                        "arg": [
                            {
                                "name": "format",
                                "desc": "",
                                "type": "string"
                            },
                            {
                                "name": "values",
                                "desc": "",
                                "type": "mixed"
                            }
                        ],
                        "isRoot": false,
                        "isConstant": false,
                        "isGlobal": false,
                        "desc": "",
                        "isMethod": true
                    }
                ],
                "isRoot": false,
                "isConstant": false,
                "isGlobal": false,
                "desc": "",
                "isMethod": true
            }
        ],
        "isRoot": false,
        "isConstant": false,
        "isGlobal": false,
        "desc": "",
        "isMethod": true
    }
]

