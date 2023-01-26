import React, {FC, useEffect, useRef, useState} from 'react';
import {Search, useAutoFocus, Table, Badge} from 'akeneo-design-system';
import {useDebounceCallback, useTranslate, useFeatureFlags} from '@akeneo-pim-community/shared';
import {
  useAttributeGroupPermissions,
  useRefreshAttributeGroups,
  useFilteredAttributeGroups,
  useGetAttributeGroupLabel,
} from '../../../hooks';
import {AttributeGroup} from '../../../models';
import {NoResults} from '../../shared';

type Props = {
  attributeGroups: AttributeGroup[];
  onAttributeGroupCountChange: (newGroupCount: number) => void;
};

const AttributeGroupsDataGrid: FC<Props> = ({attributeGroups, onAttributeGroupCountChange}) => {
  const {refreshOrder, refreshSelection, itemSelected} = useRefreshAttributeGroups();
  const {sortGranted} = useAttributeGroupPermissions();
  const getLabel = useGetAttributeGroupLabel();
  const {filteredAttributeGroups, search} = useFilteredAttributeGroups(attributeGroups);
  const translate = useTranslate();
  const [searchString, setSearchString] = useState('');
  const inputRef = useRef<HTMLInputElement>(null);
  const featureFlags = useFeatureFlags();

  useAutoFocus(inputRef);

  const debouncedSearch = useDebounceCallback(search, 300);

  const onSearch = (searchValue: string) => {
    setSearchString(searchValue);
    debouncedSearch(searchValue);
  };

  useEffect(() => {
    onAttributeGroupCountChange(filteredAttributeGroups.length);
  }, [filteredAttributeGroups.length]);

  return (
    <>
      <Search
        sticky={0}
        placeholder={translate('pim_common.search')}
        searchValue={searchString}
        onSearchChange={onSearch}
        inputRef={inputRef}
      >
        <Search.ResultCount>
          {translate(
            'pim_common.result_count',
            {itemsCount: filteredAttributeGroups.length},
            filteredAttributeGroups.length
          )}
        </Search.ResultCount>
      </Search>
      {searchString !== '' && filteredAttributeGroups.length === 0 ? (
        <NoResults
          title={translate('pim_enrich.entity.attribute_group.grid.no_search_result')}
          subtitle={translate('pim_datagrid.no_results_subtitle')}
        />
      ) : (
        <Table
          isDragAndDroppable={sortGranted && !itemSelected}
          isSelectable={true}
          onReorder={order => refreshOrder(order.map(index => attributeGroups[index]))}
        >
          <Table.Header>
            <Table.HeaderCell>{translate('pim_enrich.entity.attribute_group.grid.columns.name')}</Table.HeaderCell>
            {featureFlags.isEnabled('data_quality_insights') && (
              <Table.HeaderCell>
                {translate('akeneo_data_quality_insights.attribute_group.dqi_status')}
              </Table.HeaderCell>
            )}
          </Table.Header>
          <Table.Body>
            {filteredAttributeGroups.map(attributeGroup => (
              <Table.Row
                key={attributeGroup.code}
                isSelected={attributeGroup.selected}
                onSelectToggle={() => refreshSelection(attributeGroup.code)}
              >
                <Table.Cell>{getLabel(attributeGroup)}</Table.Cell>
                {featureFlags.isEnabled('data_quality_insights') && (
                  <Table.Cell>
                    <Badge level={attributeGroup.is_dqi_activated ? 'primary' : 'danger'}>
                      {translate(
                        `akeneo_data_quality_insights.attribute_group.${
                          attributeGroup.is_dqi_activated ? 'activated' : 'disabled'
                        }`
                      )}
                    </Badge>
                  </Table.Cell>
                )}
              </Table.Row>
            ))}
          </Table.Body>
        </Table>
      )}
    </>
  );
};

export {AttributeGroupsDataGrid};
