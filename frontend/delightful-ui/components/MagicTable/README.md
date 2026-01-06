# DelightfulTable Magic Table Component

`DelightfulTable` is an enhanced table component based on Ant Design Table, providing better loading states, scrolling behavior, and style optimization.

## Properties

| Property Name | Type                                                   | Default Value       | Description                           |
| ------------- | ------------------------------------------------------ | -------------------- | ------------------------------------- |
| loading       | boolean \| SpinProps                                   | false                | Table loading state                   |
| scroll        | { x?: number \| string \| true; y?: number \| string } | { x: 'max-content' } | Table scroll configuration            |
| ...TableProps | -                                                      | -                    | Supports all Ant Design Table props   |

## Basic Usage

```tsx
import { DelightfulTable } from '@/components/base/DelightfulTable';
import type { ColumnsType } from 'antd/es/table';

// Define data type
interface DataType {
  key: string;
  name: string;
  age: number;
  address: string;
}

// Define columns
const columns: ColumnsType<DataType> = [
  {
    title: 'Name',
    dataIndex: 'name',
    key: 'name',
  },
  {
    title: 'Age',
    dataIndex: 'age',
    key: 'age',
  },
  {
    title: 'Address',
    dataIndex: 'address',
    key: 'address',
  },
];

// Define data
const data: DataType[] = [
  {
    key: '1',
    name: 'Zhang San',
    age: 32,
    address: 'Chaoyang District, Beijing',
  },
  {
    key: '2',
    name: 'Li Si',
    age: 42,
    address: 'Pudong New Area, Shanghai',
  },
];

// Basic usage
<DelightfulTable columns={columns} dataSource={data} />

// Loading state
<DelightfulTable loading columns={columns} dataSource={data} />

// Fixed height scrolling
<DelightfulTable
  scroll={{ y: 300 }}
  columns={columns}
  dataSource={data}
/>

// Row click event
<DelightfulTable
  columns={columns}
  dataSource={data}
  onRow={(record) => ({
    onClick: () => console.log('Clicked row:', record),
  })}
/>
```

## Features

1. **Optimized loading state**: Uses DelightfulSpin component to provide a more beautiful loading effect
2. **Auto-handle scrolling**: Default x scroll set to 'max-content', prevents table content compression
3. **Row click style**: Adds pointer style to table rows, indicating user can click
4. **Empty state optimization**: Hides empty state prompt during loading to avoid flickering
5. **Flexible height control**: Control fixed table height through scroll.y property

## When to Use

-   When you need to display large amounts of structured data
-   When you need to sort, filter, paginate and perform other operations on data
-   When you need better loading state display for tables
-   When you need table content to be scrollable
-   When you need rows to be clickable

DelightfulTable component makes your table display more beautiful and user-friendly while maintaining all the powerful features of Ant Design Table.
