#include <iostream>
#include <fstream>
#include <cstdlib>
#include <cmath>

inline int pow2(int pot)
{
	int res = 1;
	for(int i=0; i<pot; i++) res *= 2;
	return res;
}

int main(int argc,char** argv)
{
	if(argc < 2)
	{
		std::cerr << "Usage: " << argv[0] << " <file>\n";
		return 1;
	}

	std::ofstream gfile(argv[1]);

	std::srand(time(NULL));

	int planet_count,byte_pos,byte,tmp_part1,tmp_part2,length;
	char bin[35];

	for(int system = 1; system <= 999; system++)
	{
		planet_count = int(20.0 * std::rand() / (RAND_MAX+1.0)); // rand(0,20);

		byte_pos = 5;
		byte = 0;

		bin[0] = planet_count << 3;
		
		planet_count += 10;

		for(int i=0; i<30; i++)
		{
			if(i<planet_count) length = int(400.0 * std::rand() / (RAND_MAX+1.0)); // rand(0,400);
			else length = 0;

			tmp_part1 = length >> 1+byte_pos;
			tmp_part2 = (length & ((1 << 2+byte_pos)-1)) << 7-byte_pos;
			if(byte_pos == 0) bin[byte] = 0;
			bin[byte] |= tmp_part1;
			byte++;
			bin[byte] = tmp_part2;
			byte_pos++;
			if(byte_pos == 8)
			{
				byte_pos = 0;
				byte++;
			}
		}

		gfile.write(bin, 35);

		for(int i=0; i<30; i++)
		{ // Planeten-Eigentuemer
			if(i<planet_count) gfile.write("                        ", 24);
			else gfile.write("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0", 24);
		}
		for(int i=0; i<30; i++)
		{ // Planetennamen
			if(i<planet_count) gfile.write("                        ", 24);
			else gfile.write("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0", 24);
		}
		for(int i=0; i<30; i++)
		{ // Allianztags
			if(i<planet_count) gfile.write("      ", 6);
			else gfile.write("\0\0\0\0\0\0", 6);
		}
	}

	std::cout << "Galaxy " << argv[1] << " successfully created.\n";
	return 0;
}
