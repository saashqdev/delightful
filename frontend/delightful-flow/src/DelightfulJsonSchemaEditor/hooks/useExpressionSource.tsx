/**
 * Manage the data source owned by the current form component
 */

import _ from 'lodash';
import { useCallback, useEffect, useState } from 'react';
import { Common } from '../types/Common';
import Schema from '../types/Schema';

export type ExpressionSourceProps = {
  allowSourceInjectBySelf: boolean;
  uniqueFormId?: string;
};

export default function useExpressionSource({
  uniqueFormId,
  allowSourceInjectBySelf,
}: ExpressionSourceProps) {
  const [innerExpressionSourceMap, setInnerSourceMap] = useState(
    {} as Record<string, Common.Options>,
  );

  useEffect(() => {
    if (allowSourceInjectBySelf) {
    }
  }, [allowSourceInjectBySelf]);

  // Convert JSON schema to a map of internal data sources for this form
  const tranSchemaToExpressionSourceMap = useCallback(
    (schema: Schema): Record<string, Common.Options> => {
      const result = {} as Record<string, Common.Options>;
      const beforeFieldsSource = [] as Common.Options;

      const gen = (root: Record<string, Schema>, parentKeys: string[]) => {
        Object.entries(root).forEach(([key, _schema]) => {
          const currentKey = [...parentKeys, key].join('.');
          result[currentKey] = _.cloneDeep(beforeFieldsSource);

          beforeFieldsSource.push({
            label: _schema.title || key,
            value: `${uniqueFormId}.${key}`,
          });
          // items has higher priority: presence of items implies an array; properties could be array or object
          if (_schema.items) {
            gen({ items: _schema.items }, [...parentKeys, key]);
          } else if (_schema.properties) {
            gen(_schema.properties, [...parentKeys, key, 'properties']);
          }
        });
      };

      gen(schema.properties || {}, ['properties']);

      return result;
    },
    [uniqueFormId],
  );

  const updateInnerSourceMap = useCallback(
    (schema: Schema) => {
      if (!allowSourceInjectBySelf) return {} as Record<string, Common.Options>;
      // if (!uniqueFormId) console.error('props uniqueFormId is missing');

      const newInnerSourceMap = tranSchemaToExpressionSourceMap(schema);

      // console.log('newInnerSourceMap', newInnerSourceMap);

      // Persist the schema-derived map
      setInnerSourceMap(newInnerSourceMap);

      return newInnerSourceMap;
    },
    [uniqueFormId, allowSourceInjectBySelf],
  );

  return {
    innerExpressionSourceMap,
    updateInnerSourceMap,
  };
}

