import {useCallback, useEffect, useState} from 'react';
import {AttributeGroup} from '../../models';
import {useUserContext} from '@akeneo-pim-community/shared';

const useFilteredAttributeGroups = (attributeGroup: AttributeGroup[]) => {
  const [filteredAttributeGroups, setFilteredAttributeGroups] = useState<AttributeGroup[]>([]);
  const userContext = useUserContext();

  useEffect(() => {
    setFilteredAttributeGroups(attributeGroup);
  }, [attributeGroup]);

  const search = useCallback(
    (searchValue: string) => {
      setFilteredAttributeGroups(
        Object.values(attributeGroup).filter((group: AttributeGroup) =>
          (group.labels[userContext.get('catalogLocale')] ?? group.code)
            .toLowerCase()
            .includes(searchValue.toLowerCase().trim())
        )
      );
    },
    [attributeGroup]
  );

  return {
    filteredAttributeGroups,
    search,
  };
};

export {useFilteredAttributeGroups};
