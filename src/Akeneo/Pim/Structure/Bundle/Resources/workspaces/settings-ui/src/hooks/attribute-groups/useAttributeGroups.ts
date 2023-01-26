import {useEffect, useState} from 'react';
import {useRouter} from '@akeneo-pim-community/shared';
import {AttributeGroup} from '../../models';

const useAttributeGroups = () => {
  const [attributeGroups, setAttributeGroups] = useState<AttributeGroup[]>([]);
  const [isPending, setIsPending] = useState(true);
  const router = useRouter();

  useEffect(() => {
    setIsPending(true);

    const load = async (): Promise<void> => {
      const response = await fetch(router.generate('pim_structure_attributegroup_rest_index'), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        method: 'GET',
      });

      if (response.ok) {
        const attributeGroups = (await response.json()) as AttributeGroup[];

        setAttributeGroups(
          attributeGroups.map((group: AttributeGroup) => {
            group.selected = false;
            return group;
          })
        );
      }
    };

    void load();
    setIsPending(false);
  }, [router]);

  return {attributeGroups, setAttributeGroups, isPending};
};

export {useAttributeGroups};
